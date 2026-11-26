# 00-Index

Start here. This vault is the second brain for the COG Audio Ministry web app.
Claude Code reads this note first, then only the notes relevant to the task.

## Core notes
- [[Project-Brief]]: identity, brand, branches, contacts, pending content, homepage structure
- [[Roadmap]]: phases and status (7 phases + production launch)
- [[Subagents]]: workflow and agent roles
- [[Backlog]]: parked ideas (in `Tasks/`)
- [[Demo-Accounts]]: demo login emails and password (fixed, do not change)

## Decisions
- [[new-project-brief-adopted]]: new brief replaces old 9-phase plan; branches/roles/Filament/packages approved
- [[second-brief-merged]]: second brief merged 2026-11-24 (branch-admin role, DB-managed content, 360px, Inter); "chapters" naming not adopted
- [[phase-order-roles-before-applications]]: why Phase 2 (roles) comes before Phase 3 (applications)
- [[package-filament]]: admin panel framework, approved 2026-11-23
- [[package-spatie-permission]]: roles and permissions, approved 2026-11-23
- [[package-breeze]]: authentication scaffolding, approved 2026-11-23
- [[package-pest]]: testing framework, approved 2026-11-23
- [[tailwind-v3-with-breeze]]: Breeze 2.4 needs Tailwind v3; kept v3 while Laravel 13 ships v4 (2026-11-24)
- [[primary-dark-contrast-token]]: `primary-dark` token for white text on filled backgrounds, WCAG AA (2026-11-24)
- [[menu-breakpoint-lg]]: full desktop menu from 1024px, hamburger below (2026-11-24)
- [[footer-tagline]]: approved footer tagline, stored in config (2026-11-24)
- [[branches-table-in-phase-1]]: branches table created in Phase 1 because services need `branch_id` (2026-11-24)
- [[marilag-is-dasma-subdivision]]: Marilag is COG Dasma's subdivision, not a branch; 4 branches (2026-11-25)
- [[timezone-asia-manila]]: `APP_TIMEZONE=Asia/Manila`, no DST (2026-11-24)
- [[image-path-convention]]: `images/` paths are bundled files; other paths are on the public disk (2026-11-24)
- [[logo-and-cover-assets]]: owner-supplied logo and cover; navbar crop approved (2026-11-24)
- [[seeders-first-or-create]]: content seeders use `firstOrCreate` so re-seeding keeps admin edits (2026-11-24)
- [[dark-mode-css-variable-tokens]]: theme colors as CSS variables, `.dark` class, no flash of wrong theme (2026-11-24)
- [[typography-matches-logo]]: Montserrat for all text, Mrs Saint Delafield script accent for "Audio" (2026-11-24)
- [[stage-palette]]: stage blue and red theme tokens, replaces sky-blue accent (2026-11-24)
- [[section-backdrops]]: subtle CSS gradient backdrops per homepage section, no blur (2026-11-24)
- [[hero-theme-verse]]: Psalm 66:8 verse in the hero, stored in config (2026-11-24)
- [[filament-resources-phase-2]]: Filament content resources moved from Phase 1 to Phase 2 (2026-11-24)
- [[churches-list-in-config]]: church list in config until the branches table is used in Phase 2 (2026-11-24)
- [[owner-wording-our-churches]]: visitor copy says "our churches", not "satellite"; church logo only in churches section (2026-11-24)
- [[card-style-and-section-rhythm]]: card background opposite its section; strict section alternation (2026-11-24)
- [[service-date-window]]: two nullable dates (`starts_on`, `ends_on`) per service for season pauses (2026-11-24)
- [[desktop-container-and-splits]]: one `max-w-7xl` container and lg two-column splits on the homepage (2026-11-24)
- [[join-form-link]]: homepage Join Us opens the Google Join Form as an interim link until Phase 3 (2026-11-24)
- [[super-admin-via-artisan-command]]: real super-admin created by `ministry:create-super-admin`; seeders make fake demo admins only (2026-11-24)
- [[admin-user-management]]: super-admin creates accounts by hand (set-password email, no typed password); branch-head edits own-branch name and email only (2026-11-25)
- [[temp-password-and-demo-emails]]: temporary passwords with forced change, 10-character password rule, branch-style demo emails (2026-11-25)
- [[full-review-2026-11-25]]: owner decisions from the Phase 1-2 full review (3-day invite link, gallery/team approval, super-admin rescue path, member-only self-delete) (2026-11-25)
- [[login-and-dashboard-decisions]]: split-photo login and all-branch services on the dashboard (2026-11-25)
- [[join-application-rules]]: no rejection email, duplicates blocked, no age minimum (2026-11-25)
- [[join-form-fields-trimmed]]: interests, experience and availability removed from the join form to keep it short (2026-11-25)
- [[filament-widget-styling]]: Filament widgets use Filament components only; custom theme later, owner approval first (2026-11-25)
- [[loading-states-approach]]: hand-built loading UI, View Transitions, reveal fallback, submit buttons disabled on next tick (2026-11-25)
- [[faq-removed]]: owner removed FAQ fully (resource, model, policy, seeder, factory, component, tests); drop migration (2026-11-25)
- [[what-we-do-removed]]: owner removed the What We Do homepage section, component kept unused (2026-11-25)
- [[gallery-upload-fix]]: gallery upload limits, storage symlink, return to list after save/delete; branch `fix/gallery-upload`, not merged (2026-11-25)
- [[gallery-thumbnails-gd]]: gallery WebP thumbnails with PHP GD, no new package, width in filename (no schema change); backfill on deploy (2026-11-25)
- [[admins-equal-per-branch]]: branch-head and branch-admin equal powers, own branch only; branch-admins manage services (2026-11-25)
- [[admin-return-to-list]]: admin Save returns to the list; Create Event opens the event page (2026-11-26)
- [[admin-review-2026-11-26]]: full admin review fixes (2026-11-26)
- [[remove-branch-head-decision]]: branch-head role removed; 1 branch-admin per branch, 2 for Dasma (2026-11-26)
- [[duty-guest-assign-decision]]: typed guest names on duty assignments, auto-confirmed (2026-11-26)
- [[duty-availability-decision]]: AM/PM availability blocks with 15:00 cutoff; open-offer swaps and self-approval rule (2026-11-26)
- [[admins-manage-not-serve]]: admins only manage; cannot be assigned, claim swaps, or mark availability (2026-11-26)

## Features
- [[public-layout]]: navbar, footer, theme tokens, placeholder home page (Phase 1, done)
- [[homepage-sections]]: homepage sections as Blade components (Phase 1, in progress; tasks in [[homepage-sections-tasks]])
- [[dark-mode]]: light/dark theme toggle on public pages (Phase 1, done pending owner check; added 2026-11-24)
- [[homepage-hooks]]: impact strip, "One sound, many churches", stay updated, ~~FAQ hidden from homepage~~ (obsolete: FAQ removed, see [[faq-removed]]) (Phase 1, done, owner browser check pending; tasks in [[homepage-hooks-tasks]])
- [[homepage-theme-polish]]: stage lighting, section rhythm, card consistency on `/` (Phase 1, done, owner browser check pending; 2026-11-24)
- [[services-section-v2]]: shorter Services section, spotlight, date window, calendar and directions (Phase 1 polish, in progress; tasks in [[services-section-v2-tasks]])
- [[member-accounts-roles]]: branches on users, roles, route throttles, policies, branch scoping, themed auth (Phase 2, in progress: 2a done and merged 2026-11-25; 2b in progress; tasks in [[member-accounts-roles-tasks]])
- [[temp-password]]: temporary passwords and forced password change, add-on to Phase 2 (done 2026-11-25, merge pending owner OK; tasks in [[temp-password-tasks]])
- [[login-dashboard-polish]]: split-photo login and member dashboard with all-branch services (Phase 2 add-on, in progress, branch `feature/login-dashboard-polish`; tasks in [[login-dashboard-polish-tasks]])
- [[dashboards]]: member dashboard and admin widgets (Phase 2 add-on, done, merged to main 2026-11-25; slice 5 built in Phase 3; replaces the missing slice 2 of [[login-dashboard-polish]]; tasks in [[dashboards-tasks]])
- [[admin-resources-polish]]: Team, Services, Gallery, Branches Filament resources restyled like Users; add-on before Phase 3 (done, merged to main 2026-11-25)
- [[join-applications]]: public join form, branch review, approval creates member account (Phase 3, planned, spec written 2026-11-25; tasks in [[join-applications-tasks]])
- [[duty-roster]]: events, duty roles, slots, assignments, accept/decline (Phase 4 v1, merged to main 2026-11-26; tasks in [[duty-roster-tasks]])
- [[duty-guest-assign]]: assign a typed guest name to a duty, auto-confirmed (Phase 4, done, merged; tasks in [[duty-guest-assign-tasks]])
- [[duty-availability]]: members mark unavailable dates and AM/PM blocks; assign respects it (Phase 4 v2a, done, merged 2026-11-26; tasks in [[duty-availability-tasks]])
- [[duty-swaps]]: open offer, claim, admin approve or reject; Roster → Swaps (Phase 4 v2b, done, merged 2026-11-26; tasks in [[duty-swaps-tasks]])
- [[admin-dashboard]]: role-based `/dashboard`, admin view for branch running (Phase 4 follow-up, done and merged 2026-11-26; tasks in [[admin-dashboard-tasks]])
- [[duty-notifications]]: bell + queued email for assignments and swaps, reminders (Phase 5 v1, in progress 2026-11-26; tasks in [[duty-notifications-tasks]])
- [[remove-branch-head]]: remove branch-head role, admin limit per branch (Phase 4, done, merged; tasks in [[remove-branch-head-tasks]])
- [[loading-states]]: progress bar, view transitions, image skeletons, submit guard, admin skeletons, scroll reveal (UI polish add-on before Phase 3, done and merged 2026-11-25; tasks in [[loading-states-tasks]])

## Folders
- `Features/`: one spec per feature (template in `CLAUDE.md`)
- `Decisions/`: why we chose things (one note per decision)
- `Tasks/`: task slices per feature, plus the Backlog

## Rules
- Use [[wikilinks]].
- Never delete notes; mark them obsolete.
