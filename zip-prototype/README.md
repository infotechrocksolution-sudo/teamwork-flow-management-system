# Teamwork Flow Management System

A lightweight, browser-based prototype for a four-person team coordinating a 30-day SaaS MVP sprint.

## Run locally
1. Extract the ZIP.
2. Open `index.html` in a modern browser.
3. Sign in using one of the demo accounts below.

## Demo sign-ins
- Sadi / `1234`
- Tanvir / `1234`
- Shevik / `1234`
- Masud / `1234`

## Included
- Overview dashboard and task metrics
- Task creation, editing, deletion, owner assignment, priorities, due days, status changes
- Kanban-style workflow board and filters
- 30-day roadmap and definition-of-done checklist
- Team ownership and workload view
- Daily stand-up updates (yesterday, today, blockers)
- CSV exports for tasks and reports
- Task and update persistence shared across all four demo logins in the same browser profile
- Automatic updates in other open tabs in the same browser profile
- Demo login stays active across page refreshes until you log out
- Workspace tools can clear all tasks (Tanvir and Shevik only) or restore any missing starter demo tasks
- Super Admin can add, edit, reorder, and remove roadmap milestones
- Responsive desktop/mobile layout
- GitHub Pages workflow file under `.github/workflows/deploy.yml`

## Task permissions
- All four members can create and edit tasks and change task status.
- Only Tanvir and Shevik can delete tasks, regardless of task status, or clear the entire task list.
- Only Tanvir and Shevik can change a task from **Done** back to **In progress**.
- Shevik is the configured Super Admin and can manage roadmap milestones.

## Important limitations — read before publishing
This is a **front-end prototype**, not a production multi-user SaaS yet.

- The four usernames and shared password are embedded in browser JavaScript. Anyone can inspect the source and bypass this login. It is suitable only for a private demo with fake data.
- Data is saved in the current browser profile and shared across the four demo logins there. Separate browsers or devices do **not** share data; configure the PHP/MySQL backend for cross-device sync.
- There is no server-side authentication, database, tenant isolation, audit log, or authorization enforcement.
- GitHub Pages is static hosting; it does not make this client-side login secure.

## To make it a real shared application
A developer should connect a backend (for example Supabase/Postgres or another managed database), implement server-validated authentication, store hashed passwords/secrets server-side, enforce role-based access on the server/database, and add automated tests. Replace the demo login before using real business data. For a low-cost deployment, GitHub can store source code and GitHub Pages can host the static prototype; a backend service is still needed for secure shared logins and synchronized data.

## Suggested MVP roles
- **Sadi — Full-stack developer:** app architecture, implementation, CI and deployment
- **Tanvir — Operations head:** requirements, process, daily coordination and handover
- **Shevik — Strategy, QC & marketing head:** scope, acceptance criteria, quality checks and release readiness
- **Masud — Security head:** threat review, permission model, security testing and release review

## GitHub Pages deployment
The included workflow deploys the static prototype to GitHub Pages when pushed to the repository's `main` branch. In the repository settings, enable Pages with **GitHub Actions** as the source. GitHub Pages may take a short time to publish after the workflow succeeds.
