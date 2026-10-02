# Teamwork Flow Management System

A lightweight browser-based prototype for a four-person team coordinating a 30-day SaaS MVP sprint.

## Demo sign-ins
- Sadi / `1234`
- Tanvir / `1234`
- Shevik / `1234`
- Masud / `1234`

## Included
- Overview dashboard and task metrics
- Create, edit, delete, assign, filter and update tasks
- 30-day roadmap and definition-of-done checklist
- Team ownership and workload view
- Daily stand-up updates (yesterday, today, blockers)
- CSV exports for tasks and roadmap
- Browser-local persistence using localStorage
- Responsive layout and GitHub Pages deployment workflow

## Important security and data limitations
This is a front-end prototype, not a production multi-user SaaS application.
- Demo usernames and shared password are embedded in browser JavaScript and can be inspected or bypassed.
- Data is stored in the current browser only; teammates do not share synchronized tasks across devices.
- There is no server-side authentication, shared database, role enforcement, audit log, or tenant isolation.
- GitHub Pages is static hosting and does not make the demo login secure.

Use only demo data. Before using this for real team work, connect a backend such as Supabase/Postgres, implement server-validated authentication and server/database-enforced permissions, and test access controls.

## Team roles
- **Sadi — Full-stack developer:** architecture, implementation, CI and deployment
- **Tanvir — Operations head:** requirements, coordination and handover
- **Shevik — Strategy, QC & marketing head:** scope, acceptance criteria and release readiness
- **Masud — Security head:** threat review, permission model and security testing

## GitHub Pages
The workflow deploys the static app on pushes to `main`. In repository Settings → Pages, choose **GitHub Actions** as the source. The app is a static prototype only; it does not require a build step.
