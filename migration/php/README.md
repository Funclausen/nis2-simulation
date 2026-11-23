# Candidate layout for hannestad.dk/private/nis2

This directory is migration source, not a production deployment.

Recommended deployment layout:

```
/private/nis2/
  index.html              participant UI (migrated)
  facilitator/            facilitator UI, protected by /private auth
  assets/nis2-client.js
  assets/nis2-admin.js
  api/
    _db.php
    _admin.php
    session.php
    answer.php
    session-create.php
    session-state.php
    session-round.php
    session-reset.php
    answers.php
```

Server-only database credentials must live outside public Git source (or in a protected local config) and must never be returned to the browser.

## Still intentionally blocked
`_admin.php` fails closed until the existing hannestad.dk/private authentication and CSRF conventions have been inspected and wired in.

## Next integration
1. Copy the current participant/facilitator UIs on the migration branch.
2. Remove Firebase imports/config only in those copies.
3. Wire their existing events to the transport adapters.
4. Run parity tests against the frozen Firebase baseline.
