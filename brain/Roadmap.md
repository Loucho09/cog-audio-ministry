# Roadmap

Related: [[00-Index]], [[Project-Brief]], [[new-project-brief-adopted]], [[second-brief-merged]], [[public-layout]]

Work one phase at a time. A phase starts only after the previous one is marked done. Owner go-ahead before each phase.

| # | Phase | Status |
|---|-------|--------|
| 1 | Setup and homepage | done (2026-11-24) |
| 2 | Member accounts and roles | done (2a and 2b merged to main 2026-11-25) |
| 3 | Join applications | done (2026-11-25; slices 1-7 and final review, last commit cc05d36; merged into main; browser check at 360px and dark mode still owner-pending) |
| 4 | Duty roster | next (not started) |
| 5 | Notifications and reminders | planned |
| 6 | Equipment, training, reports | planned |
| 7 | Production launch | planned |

## Phase 1: Setup and Homepage
**Goal:** scaffold Laravel with required packages, build a mobile-first homepage.

- Added 2026-11-24 (owner request): dark mode for public pages. See [[dark-mode]].
- [x] Done 2026-11-24: services section v2 (next-service spotlight, day cards, folded weekly list, add-to-calendar and directions links; date window per service). Merged to main. See [[services-section-v2]].
- Moved 2026-11-24: Filament resources (Service, GalleryImage, ~~Faq~~ (obsolete: FAQ removed, see [[faq-removed]]), TeamMember) go to Phase 2; Phase 1 closes without them. See [[filament-resources-phase-2]].
- [x] Done 2026-11-24 (owner request): homepage hooks (impact strip, "One sound, many churches", stay updated, ~~FAQ hidden from homepage~~ obsolete: FAQ removed 2026-11-25, see [[faq-removed]]). Browser check pending. See [[homepage-hooks]], tasks in [[homepage-hooks-tasks]]. Phase 1 stays open until the browser check and MySQL are done.
- [x] Done 2026-11-24 (owner request): homepage theme polish (stage lighting, section rhythm, card consistency). Browser check at 360px and dark mode pending. See [[homepage-theme-polish]], [[card-style-and-section-rhythm]].
- [x] Done 2026-11-24 (owner request): gallery with real photos, captions and bento layout (merged to main, b3415eb). Still open: owner consent for everyone pictured before going live; re-run `GallerySeeder` on MySQL. See [[homepage-sections]]. Phase 1 stays open.
- [x] Done 2026-11-25 (owner request, branch `feature/remove-what-we-do`, commit 8867a58): "What We Do" section removed from the homepage, plus its navbar and footer links. Component file kept but unused. 434 tests (433 pass, 1 skipped). See [[what-we-do-removed]].
- [x] Done 2026-11-25 (branch `fix/wide-screen-layout`, commit 0872038, unmerged; from `feature/remove-what-we-do`): wide-screen balance on `/` (root font scales at 1920px and 2400px; hero photo in a capped frame so the wordmark stays beside the text). Checked at 360 to 2760px, light and dark; 434 tests (433 pass, 1 skipped). Owner wide-screen check pending. See [[homepage-theme-polish]] slice G.
- [x] Done 2026-11-24 (branch `feature/what-we-do-cards`, commits 8d2e5e5, 81ab9a8, 287a5c9): What We Do is now an interactive role picker (4 tabs: FOH, Monitors, Livestream, Stage Tech; one panel with summary and detail; WAI-ARIA tabs, keyboard support). Replaces the earlier 2x2 card grid (8d2e5e5, now superseded). Checked with Playwright at 1280px light, 360px dark, and keyboard; reviewer clean; 146 tests (145 pass, 1 skipped). Owner to check the wording. See [[homepage-theme-polish]] slice E.
- [x] Done 2026-11-24 (owner request): light-mode polish (softer background #EEF2F7, darker muted text and borders, Our Churches and Join Us now light in light mode; hero ends on a crisp edge in light mode instead of a dark-to-light fade; cards white in light mode; light-mode glows, beams and vignettes cut to about a third; footer glow dark-only; Impact stats in white cards with gradient numbers; Join Us is a deep-blue band (light) with crisp white sound-wave rings and a white button; service day cards centred; dark mode otherwise unchanged). Branch `feature/light-mode-polish`. See [[stage-palette]].
- Done 2026-11-24: Playwright check at 360px, light + dark: no horizontal overflow, no console errors, all 10 sections render, theme follows device and toggle persists, gallery images load, mobile menu works. Owner-pending (non-blocking): What We Do wording, gallery photo consent before live, ~~Marilag FB link~~ (resolved 2026-11-25: Marilag is not a branch, see [[marilag-is-dasma-subdivision]]), COG Dasma own FB page?
- [x] Done 2026-11-25 (merged to main, merge 1bc4ff0): homepage audit (impeccable, 360-2760px, light and dark) scored 14/20. Items 1-6 fixed. Items 7-9 parked in [[Backlog]]. Report saved in `.impeccable/audit/` (not committed; owner to decide commit vs `.gitignore`). See [[homepage-theme-polish]] slice H.
- [x] Done 2026-11-25 (branch `fix/gallery-image-performance`, commits eaa4fdf, fdb12f1, b0881a1, merged to main 1bc4ff0): gallery WebP thumbnails made with PHP GD (800 and 1600px tiers), srcset in the grid, lazy grid. Backfill needed on production (see Phase 7). See [[gallery-thumbnails-gd]] and [[homepage-theme-polish]] slice I.
- [x] Done 2026-11-25 (branch `fix/mobile-polish`, commit 07d7f29, merged to main 1bc4ff0): mobile polish: 44px tap targets on church and calendar/directions links, `text-wrap: pretty`, light-mode church-logo watermark removed, hero pill on one line at 320-360px, equal-width Sunday time chips. See [[homepage-theme-polish]] slice J.
- Tests after these three fixes: 447 total (446 pass, 1 skipped). Reviewer run; must-fix items addressed. Browser check at 360px and wide screens still owner-pending.
- [x] Done 2026-11-25 (branch `fix/audit-backlog`, commits 4354f9f and f307974, merging to main): homepage audit backlog items 7-9 (fewer eyebrows, solid impact numbers, neutral dark hover glows, hero frame cap, targeted reduced motion). Reviewer clean; 447 tests (446 pass, 1 skipped). Owner browser check at 360px and dark mode still pending. See [[homepage-theme-polish]] slice K.

### Tasks
1. [x] Scaffold Laravel (latest stable) + Tailwind, Alpine, Filament, Spatie Permission, Breeze Blade, Pest; commit
   - Done 2026-11-24. Laravel 13.43, PHP 8.6, Breeze 2.4 Blade, Pest 5.3, Filament 5.12 (`/admin`; `canAccessPanel` returns false until Phase 2), Spatie Permission 8.5 (teams off, `HasRoles` on `User`, no roles yet), Pint, Tailwind 3.4 ([[tailwind-v3-with-breeze]]), Alpine 3, Vite.
2. [x] Base layout: header, navigation, footer; theme tokens (background, surface, text, primary, accent); Inter font
   - Done 2026-11-24. See [[public-layout]]. Tests: 41 total (40 pass, 1 skipped until `public/images/logo.png` exists). Checked at 360px, 1024px and 1280px. Reviewer run, issues fixed.
3. [x] Done 2026-11-24: Homepage sections as Blade components (in order): hero, about us, what we do, meet the team (config flag, OFF by default), upcoming services, gallery, join us, FAQ, contact, footer; commit per section group. Spec: [[homepage-sections]]. Browser check at 360px and dark mode passed (see the Done line above).
4. [x] Services model, migration, seeder (main ministry schedule). Done via slice 1 of [[homepage-sections]]. Services seeded for COG Dasmariñas only.
5. [x] `gallery_images`, ~~`faqs`~~ (obsolete: FAQ removed, see [[faq-removed]]), `team_members` tables + seeders with placeholder data (team: `is_featured` flag, full-time first). Done via slice 1 of [[homepage-sections]].
6. Semantic HTML and alt text now. SEO basics (meta, Open Graph, sitemap) can wait for the Phase 7 pass
7. [x] Done 2026-11-24: Mobile check at 360px; ensure responsive design
   - ~~Mobile check at 375px~~ (superseded 2026-11-24 by [[second-brief-merged]])

### Carry-over (already done)
- [x] Brain (Obsidian vault) set up
- [x] Subagents in `.claude/agents/`

### Still open
- [x] Git repo initialization
- [x] Git workflow and first feature branch (`feature/setup`)
- [x] `.env` and `.env.example` setup
- [x] MySQL connection (done 2026-11-24): MySQL80 service was set to Manual start, so it stopped after every reboot (no crashes in log). Now Automatic. App uses a dedicated local user `cog_audio` (temporary dev password in `.env` only; root untouched) with rights on `cog_audio` and `cog_audio_test%`. `migrate:fresh --seed` ran OK.

### Decisions (Phase 1)
- [[tailwind-v3-with-breeze]]: Tailwind v3 kept because Breeze 2.4 requires it
- [[primary-dark-contrast-token]]: new `primary-dark` token for white text on filled backgrounds
- [[menu-breakpoint-lg]]: full menu from 1024px, hamburger below
- [[footer-tagline]]: approved tagline, stored in config

## Phase 2: Member Accounts and Roles
**Goal:** set up authentication, roles, and branch scoping so members can log in and admins can manage the organization.

Status: done. 2a and 2b merged to main 2026-11-25. Split into two parts (owner decision, 2026-11-24). Spec: [[member-accounts-roles]]. Tasks: [[member-accounts-roles-tasks]].
- **2a Security core:** `branch_id` on users, Spatie roles, demo seed, `ministry:create-super-admin` command, register route removed, route throttles, `canAccessPanel`, `Gate::before`, `BelongsToBranch` scope, `AssignRole` action, Service and User policies, tests, themed auth screens. Decision: [[super-admin-via-artisan-command]].
- **2b Filament screens:** Service, GalleryImage, TeamMember, ~~Faq~~ (obsolete: FAQ removed, see [[faq-removed]]), Branch, and User resources. The "Our churches" list moves from config to the `branches` table (nullable `facebook_url`).
- [x] **Add-on done 2026-11-25** (built and reviewed on `feature/temp-password`; merge pending owner OK): temporary passwords and forced password change, plus branch-style demo emails. See [[temp-password]], [[temp-password-tasks]].
- [x] **Add-on before Phase 3 (owner decision 2026-11-25): member and admin dashboards** (`feature/dashboards`, done, merged to main 2026-11-25). Slices 1-4 built, reviewed, fixed (369 tests, 368 pass, 1 pre-existing skip); browser-checked at 360px and dark mode. Slice 5 (pending applications widget) is built during Phase 3. See [[dashboards]], [[dashboards-tasks]], [[filament-widget-styling]].
- [x] **Add-on before Phase 3 (owner request 2026-11-25): admin resources polish** (`feature/admin-resources-polish`, merged to main 2026-11-25). Team, Services, Gallery and Branches resources restyled like Users (f20bccd); FAQ resource removed (see [[faq-removed]]). 409 tests pass. Browser check at 360px still open. See [[admin-resources-polish]].
- [x] **UI polish add-on before Phase 3 (owner request 2026-11-25): loading states.** Progress bar and view transitions, image skeletons, submit guard, admin widget skeletons, scroll reveal. Merged to main 2026-11-25; 403 tests (402 pass, 1 pre-existing skip). See [[loading-states]], [[loading-states-approach]], [[loading-states-tasks]].

### Tasks
- Breeze Blade login/register; accounts created by approval only (no open signup)
- [x] Done 2026-11-25 (2a): disable the Breeze `/register` route (approval-only accounts)
- [x] Done 2026-11-25 (2a): implement `canAccessPanel` on `User` for Filament (`/admin` by role)
- [x] Done 2026-11-25 (2b): Filament resources: Service, GalleryImage, ~~Faq~~ (obsolete: FAQ removed, see [[faq-removed]]), TeamMember, Branch, User (moved from Phase 1 on 2026-11-24, see [[filament-resources-phase-2]]). Service resource needs a friendly error for the unique branch/day/time slot (from [[Backlog]]). Service resource: include starts_on/ends_on fields, branch-scoped for branch-head (see [[services-section-v2]], [[service-date-window]]).
- [x] Done 2026-11-25 (2a): `branches` table with seeder (main + 4 satellites)
- [x] Done 2026-11-25 (2a): Spatie Permission: roles `super-admin` (project owner only), `branch-head` (admin of one branch), `branch-admin` (applications and roster for one branch; cannot assign roles), `member`
- [x] Done 2026-11-25 (2a): Super-admin and branch-head seeders with fake data (`DemoUserSeeder`, local and testing only)
- Member profile page (view own info)
  - [x] Done 2026-11-25 (2a): profile shows read-only branch and role
- [x] Done 2026-11-25 (2a): `/admin` reachable only by users with a role
- [x] Done 2026-11-25 (2a): Rate limit login and password reset
- Tests:
  - [x] Done 2026-11-25 (2a): role checks, branch-scoped queries
  - [x] Done 2026-11-25 (2a): members cannot escalate roles; super-admin is the only one who assigns roles
  - [x] Done 2026-11-25 (2a): a branch-head cannot assign roles
  - [x] Done 2026-11-25 (2a): unauthorized access, role escalation, cross-branch access, mass assignment

### Notes
- Phased before Join Applications (Phase 3) because applications need branch-head login and Filament access.
- Spatie teams feature: not used (keep `branch_id` + Policies + query scopes). Revisit before this phase starts if owner wants it (see [[Backlog]]).

## Phase 3: Join Applications
**Goal:** public application form, branch approval, email notifications.

Status: done (2026-11-25). Slices 1-7 and final review done; tests 552 total, 551 passed, 1 skipped (pre-existing). Owner browser check at 360px and dark mode still pending. Spec: [[join-applications]]. Tasks: [[join-applications-tasks]]. Owner decisions: [[join-application-rules]].

### Tasks
- Public application form fields:
  - full name, age, contact number, email
  - Facebook profile link (optional)
  - branch dropdown
  - interest areas checkboxes: FOH, Monitors, Livestream, Stage Tech, Other
  - experience, availability
  - consent checkbox
- `join_applications` table: status pending/approved/rejected
- Honeypot field + rate limiting
- Queued email to that branch's branch-head, branch-admin, and super-admin when a new application arrives
- Filament: list with filters by branch and status; view, approve, reject
- Approve creates a user account (member role) and sends a set-password email
- ~~Replace the temporary homepage "Join Us" Google Form link with the in-app form~~ (done 7bc1fad, Slice 6; `ministry.join_form_url` / `MINISTRY_JOIN_FORM_URL` retired). See [[join-form-link]] (obsolete) and [[homepage-sections]].
- Tests: saved, email queued, branch-head cannot see another branch's applications, approval creates account
- ~~Old field list: age range, audio/tech experience, message~~ (superseded 2026-11-24 by [[second-brief-merged]])

## Phase 4: Duty Roster
**Goal:** event and duty management with swaps, availability, and member acceptance workflow.

### Tasks
- `events` model, migration: date, time, branch, type (Sunday Service, Revival Night, etc.)
- `duty_assignments` with statuses: assigned/accepted/declined/swap-requested/swapped
- Service classes with business logic
- Filament roster builder for branch-head and branch-admin (assign duties)
- Members accept/decline/swap via dashboard
- Dashboard: next duties, upcoming services, announcements, member's branch

### Roster rules (write Pest tests BEFORE the enforcing code)
- No double-assign at the same event time
- Cannot assign a duty the member declined
- A swap cannot leave an event without its required duties
- Swaps stay within the branch unless super-admin approves
- Availability is respected
- A swap completes only when another eligible member accepts AND branch-head or branch-admin approves

### Tests
- Rules above, swap workflow, permission checks

## Phase 5: Notifications and Reminders
**Goal:** in-app + email notifications for assignments, swaps, announcements; scheduled reminders.

### Tasks
- In-app notifications (Laravel's `Notification` and `Notifiable`)
- Email notifications per trigger (new assignment, swap approved, announcement)
- Queued job for email (Laravel `Queue`)
- Scheduled reminders (Laravel `Schedule`): day before and 2 hours before each assigned duty
- Announcements posted by branch-heads, shown on dashboard, optional email blast
- Tests per trigger

## Phase 6: Equipment, Training, Reports
**Goal:** plan only when reached; do not build earlier.

### Notes from second brief (not planned yet)
- Equipment per branch: name, status, location, last checked
- Training records: member, skill, date, trainer
- Reports: duty attendance, accepted vs declined, members per duty type, CSV export

## Phase 7: Production Launch
**Goal:** harden security, performance, and deployment readiness.

### Tasks
- Accessibility, SEO (meta per page, Open Graph, sitemap), and performance pass. Target Lighthouse 90+
- Check `text-primary` contrast for small text (e.g., the "DASMARIÑAS" eyebrow on the placeholder page); see [[primary-dark-contrast-token]]
- ~~Self-host the Inter font (see [[Backlog]])~~ superseded 2026-11-24
- Self-host Montserrat and Mrs Saint Delafield (see [[Backlog]], [[typography-matches-logo]])
- **Gallery thumbnails deploy step (2026-11-25):** run `php artisan gallery:thumbnails` once on production after deploy to backfill thumbnails. Confirm the PHP GD extension is enabled on the server (`php -m`). Add to `DEPLOY.md`. See [[gallery-thumbnails-gd]].
- Write `DEPLOY.md`: server reqs PHP 8.2+, MySQL 8, Composer, Node, extensions (including GD); `composer install --no-dev --optimize-autoloader`; `npm ci && npm run build`; production `.env` values; `key:generate`; `migrate --force`; seed only branches and roles; `storage:link`; `gallery:thumbnails`; config/route/view/event cache; HTTPS; queue worker; scheduler cron; backups of DB and uploads; first-login checklist (create super-admin, assign branch-heads, set logo, check schedule, check team flag)
- Write `PRODUCTION-CHECKLIST.md`: pages load on phone and desktop; join form sends email; admin login works; non-admin cannot reach /admin; schedule correct; images load
- **Phase 3 production notes (2026-11-25, see [[join-applications]]):**
  - Set trusted proxies so the join-form throttle (5/min per IP) and login throttle see the real client IP behind the host proxy.
  - Production `CACHE_STORE` must support atomic locks (database, redis or file). The join form uses `Cache::lock`.
  - `APP_URL` must be set correctly. Queued staff emails build admin links from it.
  - A queue worker must run for the staff email and the set-password email.
  - Replace the old Google Form QR if it is still shared offline or on Facebook, with a link or QR to `/join`.
- Full review items (2026-11-25, see [[full-review-2026-11-25]]):
  - HTTPS and proxies: `trustProxies`, `URL::forceHttps` in production, security-headers middleware (HSTS, X-Frame-Options, Referrer-Policy, CSP), `SESSION_SECURE_COOKIE=true`
  - `SESSION_DRIVER=database` creates a row per visitor: use file or redis, or schedule `session:prune`
  - **WARNING:** copying `.env.example` (APP_ENV=local) to production would run the demo seeder with a known super-admin password. Consider requiring an explicit `--demo` flag on the seeder path.
  - Run the tests and `php artisan migrate:fresh --seed` once on MySQL 8 (tests use SQLite)
  - PHP `upload_max_filesize` must be at least 4M for 4MB uploads

### Checklist (11 items)
- [ ] APP_ENV=production, APP_DEBUG=false, strong APP_KEY generated
- [ ] MySQL migrated to production; fresh migration with `php artisan migrate --force`
- [ ] HTTPS + forced redirects configured; secure cookies
- [ ] Mail provider configured (.env); test email sent to branch-head
- [ ] Queue worker running (supervisor or host equivalent)
- [ ] Cron job: `* * * * * php artisan schedule:run`
- [ ] Fake seeded data removed; only manually created super-admin remains
- [ ] `php artisan optimize` run; storage/cache permissions correct
- [ ] `php artisan gallery:thumbnails` run once after deploy
- [ ] Security review by owner: roles, branch scoping, file uploads, login rate limiting
- [ ] Phone smoke test: homepage, apply form, login, member dashboard on 360px
  - ~~on 375px~~ (superseded 2026-11-24 by [[second-brief-merged]])
- [ ] Runbook: how to deploy, back up, add a new branch

### Working Rules for phases 1–7
- **One phase at a time.** Do not start until the previous phase is marked done.
- **Small commits:** one per task; clear messages (e.g., "Phase 2: add Spatie roles").
- **Plan first:** state the plan in 3–5 bullets and wait for confirmation before large builds.
- **Run before every commit:** `php artisan test` and `./vendor/bin/pint`.
- **Seeders use fake data:** TODO placeholders for real facts (contact, address, photos).
- **Tests for anything touching members, roles, or duties.**
- **Never commit `.env`.** Keep `.env.example` updated.
- **Ask before:** adding packages, changing auth/roles, or deleting anything.
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
