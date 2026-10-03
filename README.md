# Teamwork Flow Management System

Responsive browser-based workflow dashboard for Sadi, Tanvir, Shevik and Masud.

## Centralized database backend

The app uses a PHP API and a MySQL/MariaDB table for shared state. Tasks and daily updates are stored centrally so team members can use different browsers, phones and computers and see updates on the next sync poll (approximately every 5 seconds). Saves use optimistic version checks and a three-way merge to prevent stale screens from silently replacing a teammate’s latest data. Overlapping edits are preserved in a device-local recovery backup (`tfms-conflict-backup`) and the server version wins for fields changed by both users.

### GitHub Pages frontend configuration

GitHub Pages cannot execute PHP. The frontend now reads its API base URL from `api-config.js`. After deploying the PHP backend, set `window.TFMS_API_BASE` to the HTTPS URL of the directory containing `api.php` (no trailing slash), then commit the change. Example: `https://api.example.com/teamwork-flow`.

The API currently allows credentialed CORS requests only from `https://infotechrocksolution-sudo.github.io` and uses Secure, HttpOnly, SameSite=None session cookies for cross-origin requests. The backend must be served over HTTPS. Browser third-party-cookie restrictions can still block PHP session cookies when the frontend remains on the separate `github.io` site; if that occurs, use a custom frontend domain under the same site as the API or a same-site reverse proxy. Do not place database credentials in `api-config.js` or any frontend file.

### Troubleshooting login and API responses

After uploading the latest files, open this URL in the same browser and domain as the app:

`https://YOUR-SUBDOMAIN/api.php?action=health`

It should display JSON (plain text beginning with `{"ok":true`). The health check reports whether PHP is executing, whether the database connection works, and whether the `tfms_app_state` table exists. It does not expose database credentials.

- If the URL displays an HTML page, a 404, or your hosting provider's error page instead of JSON, the request is not reaching the PHP API correctly. Confirm `api.php` is uploaded beside `index.html` in the subdomain's actual document root, PHP is enabled, and the URL is using the correct domain/path.
- If it reports that `config.php` is missing, create that file on the server from `config.example.php`.
- If it reports a database connection failure, recheck the database host/name/user/password in `config.php`.
- If it reports that `tfms_app_state` is missing, select the correct database in phpMyAdmin and run `database.sql`.
- Then test login with a demo member name (Sadi, Tanvir, Shevik or Masud) and password `1234`.

The demo login endpoint itself does not need a database connection, but loading shared tasks does. The API health response also checks that optimistic sync versioning is available; older database tables are upgraded automatically when the database user has ALTER TABLE permission. If login requests return HTML rather than JSON, this usually indicates a wrong/missing API path, a PHP execution/configuration problem, or a server-side error page—not an incorrect password.

### Hostinger deployment files

Upload these files into the same document root for your subdomain:
- `index.html`
- `api.php`
- `.htaccess`
- `config.php` (create this on the server by copying `config.example.php`; do not commit credentials)

### Database setup
1. In Hostinger hPanel, create a MySQL database and database user, and grant that user access to the database.
2. Open phpMyAdmin for that database.
3. Import/run `database.sql` after selecting the database.
4. Copy `config.example.php` to `config.php` on the server and enter the database host, database name, username and password from hPanel.
5. Ensure the site is served over HTTPS and test login from two separate devices.

The first successful save stores the app's initial task list in the database. Do not delete the database table after team members begin using the app.

## Demo sign-ins
- Sadi / `1234`
- Tanvir / `1234`
- Shevik / `1234`
- Masud / `1234`

## Permissions
- All four team members can create/edit tasks and change task status.
- Only Tanvir and Shevik can delete tasks.
- Only Tanvir and Shevik can change a task from `Done` back to `In progress`.

## Important limitations
This remains a prototype. The four demo accounts share the password `1234`, and app state is stored as one JSON document in MySQL. The API uses optimistic concurrency control and a client-side three-way merge to reduce lost updates. If both members change the same field at once, the server value is kept in the live view and the local snapshot is preserved in browser storage under `tfms-conflict-backup` for recovery. The four demo accounts still share the password `1234`; this remains a prototype and must not be used for confidential or business-critical data until individual accounts, stronger authentication, CSRF protections, audit logging, backups and a full multi-device acceptance test are completed.
