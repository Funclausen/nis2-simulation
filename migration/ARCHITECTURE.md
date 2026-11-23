# Migration: Firebase -> hannestad.dk/private/nis2

## Baseline
The original Firebase implementation is frozen on branch `baseline/firebase-v1`.

## Target
Runtime path: `https://hannestad.dk/private/nis2/`.
GitHub remains source of truth. Production files are not edited manually.

## Existing Firebase contract
Facilitator reads:
- session/id
- session/state
- answers

Facilitator writes:
- session/id
- session/state
- session/activeRound
- session/resetSignal
- answers (reset)

Participant reads:
- session/id
- session/state
- session/resetSignal

Participant writes:
- answers/{round}/{browserId}: quality, shortText, timestamp, sessionId, optional teamName

## MySQL/PHP replacement
Browser -> HTTPS PHP API -> PDO -> MySQL.
No database credentials are exposed to JavaScript.

Planned endpoints:
- GET  api/session.php?code=...
- POST api/session-create.php
- POST api/session-state.php
- POST api/session-round.php
- POST api/session-reset.php
- POST api/answer.php
- GET  api/answers.php?code=...&round=...

Participant state and facilitator results can initially poll every 1-2 seconds.

## Security boundary
Admin/facilitator endpoints must require the existing /private authenticated session (integration pending inspection of hannestad.dk/private auth).
Participant endpoints use a high-entropy session/join code and expose only data required for that simulation.
Use prepared PDO statements, server-side validation, same-origin requests, secure session cookies and CSRF protection for privileged writes.

## Deployment rule
Do not merge to main or deploy to hannestad.dk until the MySQL/PHP candidate passes functional and concurrency tests.
