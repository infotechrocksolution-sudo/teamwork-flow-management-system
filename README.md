# Teamwork Flow Management System

A lightweight workflow dashboard for a four-person team.

## Demo sign-ins
- Sadi / `1234`
- Tanvir / `1234`
- Shevik / `1234`
- Masud / `1234`

## Features
- Dashboard, task board, 30-day roadmap, team ownership and daily updates
- Shared task and stand-up data through the PHP API on the same Hostinger website
- Session-based login that survives refreshes
- Polling sync between signed-in browsers (about every 5 seconds)
- Task status updates available to all team members
- Only Tanvir and Shevik can delete tasks
- Only Tanvir and Shevik can move a task from Done back to In progress
- CSV exports

## Hostinger deployment (required for shared sync)
Upload these files into the same website document root, usually the subdomain's `public_html`:
- `index.html`
- `api.php`
- `.htaccess`

The Hostinger website must run PHP. The API creates `tfms-shared-data.json` when the first team member signs in. Ensure PHP has write permission to the website directory so it can create/update this file. The included `.htaccess` denies direct web access to the shared JSON data file on Apache-compatible hosting.

Do not deploy this PHP-backed version to GitHub Pages alone: GitHub Pages cannot execute PHP. For Hostinger, upload `api.php` alongside `index.html`.

## Important security limitations
This remains a prototype, not a production-ready secure system:
- All four accounts still use the shared demo password `1234`; replace it with individual passwords and proper password hashing before real use.
- PHP sessions and server-side task permission checks are in place, but this is not a complete production authentication system.
- The JSON file is a simple shared store, not a transactional database; concurrent edits can overwrite each other.
- Add HTTPS, backups, audit logs, stronger authentication, CSRF protections and a database before storing confidential business data.

Use demo data until those improvements are completed.

## Team roles
- **Sadi — Full-stack developer:** architecture, implementation, CI and deployment
- **Tanvir — Operations head:** requirements, coordination and handover
- **Shevik — Strategy, QC & marketing head:** scope, acceptance criteria and release readiness
- **Masud — Security head:** threat review, permissions and security testing
