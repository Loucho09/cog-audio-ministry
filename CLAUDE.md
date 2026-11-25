# Audio Ministry Web App

Web app for the Audio department of Church Of God Audio Ministry.
Goal: take this project from first commit to a live, maintained production site.

**Current phase:** 4 (Duty roster): v1, guest assign and availability (v2a) merged; swaps (v2b) next. Phase 3 (Join applications) done.

**Stack:** PHP 8.2+, Laravel (latest stable), MySQL 8, Blade, Tailwind CSS, Alpine.js, Filament (admin panel), Spatie Laravel Permission (roles), Laravel Breeze Blade (auth), Pest (tests), Pint, Laravel Mail + queue (notifications), scheduler (reminders). No Livewire/Inertia/React/Vue (outside Filament's internals), no Postgres/Supabase or extra services.
**Dev environment:** VS Code + Claude Code. Notes live in `brain/` (Obsidian vault).

## Who it serves
- **Visitors:** public pages only: homepage, learn about the ministry, submit a join application.
- **Members:** every Audio Ministry member has an account; they see their own duties, schedules, events, and announcements.
- **Branch admins:** manage their own branch's members, services, gallery, team, duties, announcements, and approve join applications; cannot assign roles. 1 per branch, 2 for COG Dasma (limit in `config/ministry.php`).
- **Super-admin:** the project owner; creates branches and assigns branch admins.

## Branches and roles (multi-branch from the start)
- Main branch: COG Dasma (Marilag Subdivision, Dasmariñas, `is_main = true`). Other branches: Trece, Silang, Jabez. Marilag is Dasma's subdivision, not a branch. Store them in a `branches` table (name, slug, address and contact fields nullable, is_main) so more can be added without code changes.
- Roles (Spatie): `super-admin` (all branches; only role that can assign roles), `branch-admin` (admin for ONE branch, no role assignment; the old `branch-head` role was removed 2026-11-26, see `brain/Decisions/remove-branch-head-decision.md`), `member` (default for approved accounts). Not logged in = visitor.
- Roles are assigned to accounts, never hardcoded to a person. Members can never change their own role.
- Every branch-scoped record (members, events, services, join applications, duty assignments) has a `branch_id`. Enforce scoping in Policies and query scopes (and Filament resources), never only in the UI. Write tests for it. (Spatie "teams" is not used for now; see `brain/Decisions/`.)
- Join applications: the applicant picks a branch; that branch's branch-admins, plus the super-admin, get a queued email.
- Member accounts are created by approval only (no open signup). Approving an application creates the account and sends a set-password email.
- `/admin` (Filament) is only for users with an admin role.

## Brand and content
- Name: "COG Audio Ministry", subheader "Dasmariñas". Logo: official Facebook logo at `public/images/logo.png` (do not redraw; show a text placeholder until the file exists).
- Colors are Tailwind theme tokens, not hardcoded hex: `background` #EEF2F7, `surface` #FFFFFF, `text` #0F172A, `primary` #0284C7 (stage blue), `accent` #E11D48 (stage red for fills, bars, glows; small red text uses `accent-text` #BE123C / dark #FB7185 for AA contrast; never body text) (light values; dark mode is near-black #07080D with #38BDF8 / #F43F5E, values live in `resources/css/app.css`). Palette matches the cover photo; see `brain/Decisions/stage-palette.md`. Exception: text and overlays on photos (e.g. the hero) may use `white`/`black`, since the photo is dark in both themes.
- Light and dark mode: navbar toggle, defaults to the device setting. New public UI must work in both themes.
- Fonts match the logo: Montserrat for all text; Mrs Saint Delafield (`font-script`) only as a decorative accent (e.g. "Audio" in the hero), never for body text. Style: clean minimalist, spacious, mobile-first.
- Copy: English, warm, simple, respectful, Tagalog-friendly tone.
- Contacts and flags live in `config/ministry.php`, never hardcoded in views. Service schedule is seeded into the `services` table and managed in Filament.
- Gallery images and team members are stored in the database and managed in Filament. Placeholder images until real photos are added.
- "Meet the Team" is behind a config flag, default OFF. When on, it shows team members marked `is_featured` (full-time first).
- Homepage sections are Blade components. Contact section shows a map only when the address is filled in.
- Full details in `brain/Project-Brief.md`. Use TODO placeholders for pending content (vision/mission, team list, Messenger link, address, photos). Never invent names, phone numbers, or church facts.

## Second brain (read this first)
- Before any task, read `brain/00-Index.md`, then only the notes relevant to the task.
- `brain/Roadmap.md` = phases and status. `brain/Features/` = one spec per feature. `brain/Decisions/` = why we chose things. `brain/Tasks/Backlog.md` = parked ideas.
- Use [[wikilinks]]. Never delete notes; mark them obsolete.
- After finishing work, update the brain (delegate to `brain-keeper`).

## Roadmap: from start to production
Work one phase at a time. Do not start a phase until the previous one is marked done in `brain/Roadmap.md`.

1. **Setup and homepage:** scaffold Laravel + packages, layout and theme tokens, homepage sections (hero, about, what we do, meet the team behind flag, upcoming services, gallery, join us, contact, footer), services, gallery and team models + seeders (FAQ was later removed; see `brain/Decisions/faq-removed.md`), mobile check at 360px.
2. **Member accounts and roles:** Breeze login, approval-only accounts, Spatie roles (incl. `branch-admin`), branches, seeders, rate-limited login and password reset, tests for role escalation, cross-branch access, mass assignment, and "branch admins cannot assign roles".
3. **Join applications:** public form with honeypot and rate limiting, pending/approved/rejected status, queued email to branch-admins + super-admin, Filament approve/reject with branch and status filters, approval creates account + set-password email, tests.
4. **Duty roster:** events, duty assignments and swaps with rules in service classes (rule tests written first), Filament roster, member dashboard with accept/decline/swap, tests.
5. **Notifications and reminders:** in-app + email for assignments, swaps, announcements; reminders the day before and 2 hours before each duty; tests.
6. **Equipment, training, reports:** plan in detail only when reached. Do not build earlier.
7. **Production launch:** accessibility/SEO/sitemap/performance pass (Lighthouse 90+), `DEPLOY.md`, `PRODUCTION-CHECKLIST.md`, checklist below.

(Phases 2 and 3 were swapped from the original brief so roles exist before the application approval screens. See `brain/Decisions/`.)

Details for each phase belong in `brain/Roadmap.md` and `brain/Features/`, not in this file.

## Subagents (`.claude/agents/`)
Delegate instead of doing everything in the main conversation. This keeps context small.
- `frontend-builder`: Blade, Tailwind, Alpine UI. Never touches migrations, models, or auth.
- `backend-builder`: migrations, models, auth, roles, schedules, notifications, tests.
- `fullstack`: features that span both front end and back end, built in slices; use when unsure which builder fits.
- `reviewer`: read-only review of recent changes (bugs, security, Laravel conventions).
- `brain-keeper`: edits only `brain/`.

Standard loop for every feature: **build → review → fix → record in brain**.

## Coding conventions
- Follow Laravel conventions: Form Requests for validation, Policies/Gates for authorization, Eloquent relationships, resource controllers, named routes.
- Never trust input: validate server-side, escape output, use CSRF (default in Blade forms), no raw SQL with user input.
- Mass assignment: always define `$fillable`.
- Keep controllers thin; move logic to Actions/Services when it grows.
- Write at least one feature test per feature (happy path + authorization failure).
- Small, focused commits with clear messages. One feature per branch (`feature/<name>`). Run `php artisan test` and `./vendor/bin/pint` before every commit; fix failures first.
- Semantic HTML, alt text on images, meta title/description per page.
- Uploaded images go in `storage/app/public` (with `php artisan storage:link`). No secrets in the repo; `.env.example` has empty values.
- Seeders must give a working demo with `php artisan migrate:fresh --seed` (fake members, events, duties, applications).
- No new Composer/NPM packages without asking and noting the reason in `brain/Decisions/`.

## Definition of done (every feature)
- [ ] Matches its spec in `brain/Features/`
- [ ] Works on mobile and desktop
- [ ] Validation and authorization in place
- [ ] Tests pass (`php artisan test`)
- [ ] `reviewer` run and issues addressed
- [ ] Brain updated (spec status, decision notes, Roadmap)

## Production checklist (Phase 7, before going live)
1. `APP_ENV=production`, `APP_DEBUG=false`, a fresh `APP_KEY`. Never commit `.env`; keep `.env.example`.
2. HTTPS on the domain. Force HTTPS redirects.
3. Production MySQL: take a backup first, then run migrations.
4. Mail provider configured and a test email sent.
5. Queue worker running (supervisor or hosting equivalent).
6. Scheduler cron entry: `* * * * * php artisan schedule:run`.
7. Remove seeded fake data; keep only the super-admin account, created manually.
8. Run `php artisan optimize`; check storage and cache permissions.
9. Security review by the owner: roles, branch scoping, file uploads, login rate limiting.
10. Smoke test on a phone: homepage, apply form, login, member dashboard; confirm a non-admin cannot reach `/admin`.
11. Short runbook: how to deploy, back up, and add a branch.

## Adding a new feature (anti-overwhelm protocol)
When the user asks for something new, mid-project or after launch, follow these steps in order. Do not jump straight to code.

1. **Pause and restate.** In 2-3 sentences, say what you understood. Ask at most 3 clarifying questions if truly needed.
2. **Check the fit.** Read `brain/Roadmap.md`. Say which phase it belongs to and whether it blocks or is blocked by anything.
3. **Write a mini spec** in `brain/Features/<feature-name>.md`: goal, who uses it, screens/routes, data changes, permissions, out of scope.
4. **Slice it.** Break it into small tasks, each touching about 5 files or fewer and finishable in one sitting. List them in `brain/Tasks/`.
5. **Get a go-ahead** on the plan before large changes.
6. **Build one slice at a time** with the right subagent. Finish, test, and commit each slice before the next.
7. **Review and record** with `reviewer` and `brain-keeper`.

Rules that prevent overload:
- **One feature at a time.** If the user adds more ideas while one is in progress, park them in `brain/Tasks/Backlog.md` and keep going.
- **Scope stays fixed.** If the work grows beyond the spec, stop and ask. Do not silently expand it.
- **No drive-by refactors.** Only change files the current slice needs. Mention other issues in the Backlog instead of fixing them.
- **Read narrowly.** Open only the files you need. Never scan `vendor/`, `node_modules/`, `storage/`, or build output.
- **Keep context fresh.** Suggest `/compact` or `/clear` between features, and re-read `CLAUDE.md` and the feature spec when starting.
- **Big features get phases.** If a feature needs more than about 5 slices, propose splitting it into v1 (smallest useful version) and v2.
- **Ask before:** changing the database schema in ways that affect existing data, adding packages, touching auth/roles, or deleting anything.

## Feature spec template (`brain/Features/<name>.md`)
```
# <Feature name>
Status: planned | in progress | done
Phase: <number>
Goal:
Users: visitor | member | branch-admin | super-admin
Screens / routes:
Data changes (tables, columns):
Permissions:
Out of scope:
Tasks: [[link to tasks]]
Notes / decisions: [[link]]
```

## Useful commands
- `php artisan serve` / `npm run dev`: run locally
- `php artisan migrate:fresh --seed`: reset local database
- `php artisan test`: run tests
- `./vendor/bin/pint`: format PHP code

## Communication style
- Explain steps simply, one small step at a time. The owner is a developer but may be new to Claude Code workflows.
- Before building a feature, state the plan in 3-5 bullets and wait for confirmation.
- Explain new Laravel concepts (e.g. Policies, Filament resources, queues) briefly the first time they appear.
- At the end of each phase: say what was built, how to check it in the browser, and which TODO placeholders remain. Update the "Current phase" line at the top of this file.
- When unsure about a ministry decision (admin names, content, approvals), ask the owner instead of guessing.
