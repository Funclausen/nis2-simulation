# Deploy to hannestad.dk/private/nis2

Participants use `/private/nis2/?s=SESSIONCODE`. The existing portal issues
its session cookie for `/privat/`, so facilitators use `/privat/nis2/`.
The participant release redirects `/private/nis2/facilitator.html` to that
protected page. The facilitator page and API use the existing admin role and
session CSRF token.

## Prepare and test

1. Check out the intended commit outside `public_html`. Review the existing
   portal login and confirm that `.hannestad-private/mysql.json` points to
   the private MySQL database. Keep credentials outside the web root.
2. Run `sh deployment/prepare.sh STAGE/public_html` in a private scratch
   directory under the hosting account. The stage should have its own
   `.hannestad-private` next to `public_html`. Copy the portal PHP required
   by its login into that directory without copying portal data. Link
   `STAGE/.nis2-mysql.json` to the existing server-only MySQL config.
   The staged portal login uses its own SQLite fixture under `data/`.
3. Check PHP and JS syntax. On the first installation, run
   `php deployment/schema.php STAGE/public_html` to create the three
   NIS2 tables in the existing private MySQL database. This command expects
   a clean NIS2 schema and does not change the portal tables.
4. Run `python3 deployment/smoke.py STAGE/public_html`. It exercises the
   actual portal login with isolated users and the NIS2 API from 30 concurrent
   clients, then removes its synthetic session. Check the two browser views,
   including the QR code and the facilitator dashboard.
5. Move the staged NIS2 directories into the corresponding paths under
   `public_html` on the same filesystem after verifying both destinations
   are free. Link `~/.nis2-mysql.json` to the same server-only configuration. Verify the public participant route, the facilitator redirect
   and the signed-out access checks. Confirm portal login still loads.

For rollback, move the two NIS2 directories out of the web root without
touching portal files or dropping NIS2 tables. The former hosted Firebase
version is separate from these paths.
