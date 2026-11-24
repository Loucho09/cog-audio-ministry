# Decision: Merge second project brief (2026-11-24)

Date: 2026-11-24
Related: [[new-project-brief-adopted]], [[Roadmap]], [[Project-Brief]], [[00-Index]]

## Context
The owner pasted a second brief that uses "chapters" instead of "branches". The owner chose to MERGE it into the existing plan (no replacement).

## Adopted
- New `branch-admin` role (see [[Project-Brief]])
- Application notifications (queued) to branch-head + branch-admin of the chosen branch + super-admin
- Gallery images and FAQs stored in DB, managed in Filament
- Team members from DB with `is_featured` flag (full-time first); section still behind flag, default OFF
- Honeypot + rate limiting on join form
- SEO: meta title/description per page, Open Graph, sitemap (Phase 7 pass)
- Accessibility; Lighthouse 90+ target
- Responsive from 360px (was 375px)
- Inter font via Google Fonts
- English with warm, Tagalog-friendly copy
- Duty roster rules list (see [[Roadmap]] Phase 4)
- Reminders day-before and 2h-before each duty
- Approval creates account + sends set-password email
- `DEPLOY.md` and `PRODUCTION-CHECKLIST.md` in Phase 7
- Run `php artisan test` and `./vendor/bin/pint` before every commit

## Not adopted
- "Chapters" naming (keep "branches")
- `config/brand.php` (keep `config/ministry.php`)
- `show_team` default true (stays OFF)
- Spatie teams feature (keep `branch_id` + Policies + query scopes; revisit before Phase 2 if owner wants teams, see [[Backlog]])
- Autonomous no-stop workflow (keep one phase at a time, owner go-ahead)
- 14-step phase order (keep roles before applications, see [[phase-order-roles-before-applications]])
- Decisions/Future ideas sections in CLAUDE.md (they stay in `brain/Decisions/` and `brain/Tasks/Backlog.md`)

## Rules kept
- 7-phase roadmap, one phase at a time
- Ask before adding packages
