<?php
declare(strict_types=1);
require __DIR__ . '/_db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') nis2_json(['error' => 'method_not_allowed'], 405);
$b = nis2_body();
$code = strtoupper(trim((string)($b['code'] ?? '')));
$browserId = trim((string)($b['browserId'] ?? ''));
$teamName = trim((string)($b['teamName'] ?? ''));
$round = filter_var($b['round'] ?? null, FILTER_VALIDATE_INT);
$resetVersion = filter_var($b['resetVersion'] ?? null, FILTER_VALIDATE_INT);
$quality = (string)($b['quality'] ?? '');
$shortText = trim((string)($b['shortText'] ?? ''));

if ($code === '' || $browserId === '' || $resetVersion === false || $resetVersion < 0 || $round === false || $round < 0 || $round > 7 ||
    !in_array($quality, ['good','neutral','bad'], true) || $shortText === '') {
    nis2_json(['error' => 'invalid_payload'], 422);
}
if (strlen($browserId) > 64 || mb_strlen($teamName) > 120 || mb_strlen($shortText) > 500) {
    nis2_json(['error' => 'payload_too_long'], 422);
}

$pdo = nis2_db();
$pdo->beginTransaction();
try {
    $q = $pdo->prepare('SELECT id, status, reset_version FROM nis2_sessions WHERE session_code = ? FOR UPDATE');
    $q->execute([$code]);
    $session = $q->fetch();
    if (!$session || $session['status'] !== 'active' || (int)$session['reset_version'] !== $resetVersion) {
        $pdo->rollBack();
        nis2_json(['error' => 'session_not_active'], 409);
    }

    $q = $pdo->prepare(
        'INSERT INTO nis2_teams (session_id, browser_id, team_name)
         VALUES (?, ?, NULLIF(?, ""))
         ON DUPLICATE KEY UPDATE team_name=VALUES(team_name), last_seen_at=CURRENT_TIMESTAMP'
    );
    $q->execute([$session['id'], $browserId, $teamName]);

    $q = $pdo->prepare('SELECT id FROM nis2_teams WHERE session_id=? AND browser_id=?');
    $q->execute([$session['id'], $browserId]);
    $teamId = (int)$q->fetchColumn();

    $q = $pdo->prepare(
        'INSERT INTO nis2_answers (session_id, team_id, round_no, quality, short_text)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE quality=VALUES(quality), short_text=VALUES(short_text), answered_at=CURRENT_TIMESTAMP'
    );
    $q->execute([$session['id'], $teamId, $round, $quality, $shortText]);
    $pdo->commit();
    nis2_json(['ok' => true]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    nis2_json(['error' => 'server_error'], 500);
}
