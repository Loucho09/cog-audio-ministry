# Roadmap

Related: [[00-Index]], [[Project-Brief]], [[new-project-brief-adopted]]

Work one phase at a time. A phase starts only after the previous one is marked done.

| # | Phase | Status |
|---|-------|--------|
| 1 | Setup and homepage | in progress |
| 2 | Member accounts and roles | planned |
| 3 | Join applications | planned |
| 4 | Duty roster | planned |
| 5 | Notifications and reminders | planned |
| 6 | Equipment, training, reports | planned |
| 7 | Production launch | planned |

## Phase 1: Setup and Homepage
**Goal:** scaffold Laravel with required packages, build a mobile-first homepage.

### Tasks
1. Scaffold Laravel (latest stable) + Tailwind, Alpine, Filament, Spatie Permission, Breeze Blade, Pest; commit
2. Base layout: header, navigation, footer; theme tokens (background, surface, text, primary, accent)
3. Homepage sections (in order): hero, about us, what we do, meet the team (config flag, OFF by default), upcoming services, gallery, join us, FAQ, contact, footer; commit per section group
4. `services` model, migration, seeder (main ministry schedule)
5. Mobile check at 375px; ensure responsive design

### Carry-over (already done)
- [x] Brain (Obsidian vault) set up
- [x] Subagents in `.claude/agents/`

### Still open
- [ ] Git repo initialization
- [ ] `.env` and `.env.example` setup + MySQL
- [ ] Git workflow and first feature branch

## Phase 2: Member Accounts and Roles
**Goal:** set up authentication, roles, and branch scoping so members can log in and admins can manage the organization.

### Tasks
- Breeze Blade login/register; accounts created by approval only (no open signup)
- `branches` table with seeder (main + 4 satellites)
- Spatie Permission: roles `super-admin` (project owner only), `branch-head` (admin of one branch), `member`
- Super-admin and branch-head seeders with fake data
- Member profile page (view own info)
- Tests: role checks, branch-scoped queries, members cannot escalate roles, super-admin is the only one who assigns roles

### Notes
- Phased before Join Applications (Phase 3) because applications need branch-head login and Filament access.

## Phase 3: Join Applications
**Goal:** public application form, branch-head approval, email notifications.

### Tasks
- Public application form: name, contact, age range, audio/tech experience, branch choice, availability, message
- `join_applications` table: status pending/approved/rejected
- Validation and spam protection (rate limiting)
- Queued email to that branch's branch-head when new application arrives
- Filament list for branch-head: view, approve, reject
- Tests: saved, email queued, branch-head cannot see another branch's applications

## Phase 4: Duty Roster
**Goal:** event and duty management with swaps, availability, and member acceptance workflow.

### Tasks
- `events` model, migration: date, time, branch, type (Sunday Service, Revival Night, etc.)
- `duty_assignments` with statuses: assigned/accepted/declined/swap-requested/swapped
- Service classes with business logic: no double-booking, swap rules (needs other member's accept + branch-head approval)
- Members accept/decline/swap via dashboard
- Filament roster builder for branch-head (assign duties)
- Tests: double-booking rules, swap workflow, permission checks

## Phase 5: Notifications and Reminders
**Goal:** in-app + email notifications for assignments, swaps, announcements; scheduled reminders.

### Tasks
- In-app notifications (Laravel's `Notification` and `Notifiable`)
- Email notifications per trigger (new assignment, swap approved, announcement)
- Queued job for email (Laravel `Queue`)
- Scheduled reminders (Laravel `Schedule`): assignment reminders before service, member availability requests
- Tests per trigger

## Phase 6: Equipment, Training, Reports
**Goal:** plan only when reached; do not build earlier.

## Phase 7: Production Launch
**Goal:** harden security, performance, and deployment readiness.

### Checklist (11 items)
- [ ] APP_ENV=production, APP_DEBUG=false, strong APP_KEY generated
- [ ] MySQL migrated to production; fresh migration with `php artisan migrate --force`
- [ ] HTTPS + forced redirects configured; secure cookies
- [ ] Mail provider configured (.env); test email sent to branch-head
- [ ] Queue worker running (supervisor or host equivalent)
- [ ] Cron job: `* * * * * php artisan schedule:run`
- [ ] Fake seeded data removed; only manually created super-admin remains
- [ ] `php artisan optimize` run; storage/cache permissions correct
- [ ] Security review by owner: roles, branch scoping, file uploads, login rate limiting
- [ ] Phone smoke test: homepage, apply form, login, member dashboard on 375px
- [ ] Runbook: how to deploy, back up, add a new branch

### Working Rules for phases 1–7
- **One phase at a time.** Do not start until the previous phase is marked done.
- **Small commits:** one per task; clear messages (e.g., "Phase 2: add Spatie roles").
- **Plan first:** state the plan in 3–5 bullets and wait for confirmation before large builds.
- **Seeders use fake data:** TODO placeholders for real facts (contact, address, photos).
- **Tests for anything touching members, roles, or duties.**
- **Never commit `.env`.** Keep `.env.example` updated.
- **New Laravel concepts:** explain briefly the first time (e.g., "this uses Laravel's built-in notification queue").

---

## Obsolete (superseded 2026-11-23)

The original 9-phase roadmap has been replaced by the brief adopted today. See [[new-project-brief-adopted]] for the rationale and main changes.

| # | Phase | Status |
|---|-------|--------|
| 1 | Foundation | (old) |
| 2 | Public homepage | (old) |
| 3 | Auth and roles | (old) |
| 4 | Member features | (old) |
| 5 | Join applications | (old) |
| 6 | Admin dashboard | (old) |
| 7 | Hardening | (old) |
| 8 | Production launch | (old) |
| 9 | Maintenance | (old) |
