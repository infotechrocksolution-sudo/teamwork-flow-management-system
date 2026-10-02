# Teamwork Flow Management System

Responsive browser-based workflow dashboard for Sadi, Tanvir, Shevik and Masud.

## Centralized database backend

The app uses a PHP API and a MySQL/MariaDB table for shared state. Tasks and daily updates are stored centrally so team members can use different browsers, phones and computers and see updates on the next sync poll (approximately every 5 seconds).

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
This remains a prototype. The four demo accounts share the password `1234`, and app state is stored as one JSON document in MySQL. The API serializes writes, but simultaneous edits can still overwrite one another if users save from stale screens. Before confidential or business-critical use, replace demo authentication with individually managed accounts, use a normalized relational schema, add CSRF protections and audit logging, and test backups and permissions.
