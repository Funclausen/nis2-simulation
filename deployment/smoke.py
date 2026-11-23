"""Exercise the staged portal login and the NIS2 application against Simply MySQL."""

import concurrent.futures
import http.cookiejar
import json
import os
from pathlib import Path
import re
import secrets
import signal
import socket
import subprocess
import sys
import time
from urllib.error import HTTPError
from urllib.parse import urlencode
from urllib.request import HTTPCookieProcessor, Request, build_opener

DOCROOT = Path(sys.argv[1]).resolve()
PORTAL = DOCROOT.parent / ".hannestad-private"
PUBLIC = "/private/nis2"
ADMIN = "/privat/nis2"
PASSWORD = secrets.token_urlsafe(24)
CODES = []

subprocess.run(
    ["php", "-r", r"""
    require $argv[1];
    foreach (['admin','member'] as $role) {
        query('INSERT INTO users(name,email,password,role,apps,created) VALUES(?,?,?,?,?,?)',
            [$role,$role.'@example.invalid',password_hash($argv[2], PASSWORD_DEFAULT),$role,'[]',time()]);
    }
    """, str(PORTAL / "core.php"), PASSWORD],
    check=True,
)
with socket.socket() as port_socket:
    port_socket.bind(("127.0.0.1", 0))
    port = port_socket.getsockname()[1]
BASE = f"http://127.0.0.1:{port}"


def request(path, expected=200, *, client=None, data=None, form=None, csrf=None):
    client = client or build_opener()
    headers = {}
    body = None
    if data is not None:
        headers["Content-Type"] = "application/json"
        body = json.dumps(data).encode()
    if form is not None:
        headers["Content-Type"] = "application/x-www-form-urlencoded"
        body = urlencode(form).encode()
    if csrf is not None:
        headers["X-CSRF-Token"] = csrf
    try:
        response = client.open(Request(BASE + path, data=body, headers=headers), timeout=8)
    except HTTPError as error:
        response = error
    with response:
        content = response.read().decode()
        assert response.status == expected, f"{path}: {response.status} (expected {expected})"
        return json.loads(content) if "application/json" in response.headers.get("Content-Type", "") else content


def login(role):
    client = build_opener(HTTPCookieProcessor(http.cookiejar.CookieJar()))
    page = request("/privat/?page=login", client=client)
    csrf = re.search(r'name="csrf" value="([^"]+)"', page)[1]
    request("/privat/?page=login", client=client, form={
        "csrf": csrf, "action": "login", "email": f"{role}@example.invalid", "password": PASSWORD,
    })
    return client


def participant(browser, version, *, round_no=0, expected=200):
    return request(PUBLIC + "/api/answer.php", expected, data={
        "code": code, "round": round_no, "quality": "good" if browser % 2 == 0 else "bad",
        "browserId": f"test-browser-{browser}", "teamName": f"Testhold {browser}",
        "shortText": "Testbeslutning", "resetVersion": version,
    })


def admin_post(action, data):
    return request(ADMIN + f"/api/{action}.php", client=admin, data=data, csrf=token)


with (DOCROOT.parent / "php.log").open("w") as log:
    server = subprocess.Popen(
        ["php", "-S", f"127.0.0.1:{port}", "-t", str(DOCROOT)],
        env={**os.environ, "PORTAL_TEST": "1", "PHP_CLI_SERVER_WORKERS": "4"},
        start_new_session=True, stdout=log, stderr=log,
    )
    try:
        for attempt in range(30):
            try:
                request(PUBLIC + "/index.html")
            except OSError:
                time.sleep(0.1)
            else:
                break
        else:
            raise RuntimeError("Staging server did not start.")

        request(ADMIN + "/facilitator.php", 401)
        request(ADMIN + "/api/session-create.php", 401, data={})
        member = login("member")
        request(ADMIN + "/facilitator.php", 401, client=member)
        request(ADMIN + "/api/session-create.php", 401, client=member, data={})
        admin = login("admin")
        page = request(ADMIN + "/facilitator.php", client=admin)
        token = re.search(r'<meta name="csrf-token" content="([a-f0-9]{64})">', page)[1]
        request(ADMIN + "/api/session-create.php", 403, client=admin, data={})
        request(ADMIN + "/api/session-create.php", 403, client=admin, data={}, csrf="invalid")
        code = admin_post("session-create", {"title": "NIS2 deployment smoke test", "expectedParticipants": 30})["session"]["code"]
        CODES.append(code)
        state_path = PUBLIC + f"/api/session.php?code={code}"
        answers_path = ADMIN + f"/api/answers.php?code={code}"
        assert request(state_path)["session"]["status"] == "waiting"
        participant(0, 0, expected=409)
        admin_post("session-state", {"code": code, "status": "active"})
        admin_post("session-state", {"code": code, "status": "active"})
        assert request(state_path)["session"]["status"] == "active"
        with concurrent.futures.ThreadPoolExecutor(max_workers=30) as workers:
            assert all(answer["ok"] for answer in workers.map(lambda index: participant(index, 0), range(30)))
        answers = request(answers_path, client=admin)["answers"]
        assert len(answers) == 30
        assert sum(answer["quality"] == "good" for answer in answers) == 15
        assert len({answer["team_name"] for answer in answers}) == 30
        request(answers_path, 401)
        admin_post("session-round", {"code": code, "round": 1})
        assert request(state_path)["session"]["active_round"] == 1
        participant(0, 0, round_no=1)
        assert len(request(answers_path, client=admin)["answers"]) == 31
        admin_post("session-reset", {"code": code})
        session = request(state_path)["session"]
        assert session["reset_version"] == 1 and session["active_round"] == 0 and session["status"] == "waiting"
        assert request(answers_path, client=admin)["answers"] == []
        admin_post("session-state", {"code": code, "status": "active"})
        participant(0, 0, expected=409)
        participant(0, 1)
        admin_post("session-state", {"code": code, "status": "finished"})
        assert request(state_path)["session"]["status"] == "finished"
        participant(1, 1, expected=409)
        print("PASS: portal login, admin/member, CSRF, 30 concurrent clients, history, reset and finish.")
    finally:
        os.killpg(server.pid, signal.SIGTERM)
        server.wait(timeout=5)
        if CODES:
            subprocess.run(
                ["php", "-r", r"""
                require $argv[1];
                $q = nis2_db()->prepare('DELETE FROM nis2_sessions WHERE session_code = ? AND title = ?');
                $q->execute([$argv[2], 'NIS2 deployment smoke test']);
                if ($q->rowCount() !== 1) exit(1);
                """, str(DOCROOT / "private/nis2/api/_db.php"), CODES[0]],
                check=True,
            )
