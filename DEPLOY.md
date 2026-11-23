# Deploy to hannestad.dk/private/nis2

The migration candidate is complete except for two environment-specific bindings that must be taken from the existing hannestad.dk/private installation.

## 1. Database
Create a MySQL database/user in Simply and run `database/schema.sql`.
Copy `config.example.php` to `config.local.php` in the NIS2 root and insert the Simply MySQL host/database/user/password.
`.htaccess` blocks web access to local config files.

## 2. Existing /private authentication
Do NOT create a second login.
Edit only `api/_admin.php` after inspecting the existing /private PHP session:
- `nis2_admin_authenticated()` must return the existing logged-in/admin state.
- `nis2_admin_user_id()` should return the existing user id when available.
- `nis2_admin_csrf_valid()` must validate the existing CSRF token/session convention.

Until this is wired, facilitator/admin endpoints deliberately return 401/403.

## 3. URLs
Participant: `https://hannestad.dk/private/nis2/?s=SESSIONCODE`
Facilitator: `https://hannestad.dk/private/nis2/facilitator.html`

The facilitator creates a unique session and regenerates the QR code with that session code.

## 4. Acceptance test
1. Login to /private and open facilitator.
2. Create/start session.
3. Scan QR in two separate browsers.
4. Confirm both move from waiting to active.
5. Submit different answers in round 1; confirm facilitator counts/distribution.
6. Advance round; submit answers; return to dashboard and confirm prior-round history remains.
7. Reset; confirm participants reset and old answers disappear.
8. Finish session; confirm participants return to waiting/non-active state.
9. Repeat with 30 browser sessions or scripted clients before production use.

## Rollback
The untouched Firebase baseline is branch `baseline/firebase-v1`.
