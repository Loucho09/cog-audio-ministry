# 00-Index

Start here. This vault is the second brain for the COG Audio Ministry web app.
Claude Code reads this note first, then only the notes relevant to the task.

## Core notes
- [[Project-Brief]]: identity, brand, branches, contacts, pending content, homepage structure
- [[Roadmap]]: phases and status (7 phases + production launch)
- [[Subagents]]: workflow and agent roles
- [[Backlog]]: parked ideas (in `Tasks/`)

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

## Features
- [[public-layout]]: navbar, footer, theme tokens, placeholder home page (Phase 1, done)
- [[homepage-sections]]: homepage sections as Blade components (Phase 1, in progress; tasks in [[homepage-sections-tasks]])
- [[dark-mode]]: light/dark theme toggle on public pages (Phase 1, done pending owner check; added 2026-11-24)
- [[homepage-hooks]]: impact strip, "One sound, many churches", stay updated, FAQ hidden from homepage (Phase 1, done, owner browser check pending; tasks in [[homepage-hooks-tasks]])
- [[homepage-theme-polish]]: stage lighting, section rhythm, card consistency on `/` (Phase 1, done, owner browser check pending; 2026-11-24)
- [[services-section-v2]]: shorter Services section, spotlight, date window, calendar and directions (Phase 1 polish, in progress; tasks in [[services-section-v2-tasks]])
- [[member-accounts-roles]]: branches on users, roles, route throttles, policies, branch scoping, themed auth (Phase 2, in progress: 2a done and merged 2026-11-25; 2b in progress; tasks in [[member-accounts-roles-tasks]])
- [[temp-password]]: temporary passwords and forced password change, add-on to Phase 2 (done 2026-11-25, merge pending owner OK; tasks in [[temp-password-tasks]])

## Folders
- `Features/`: one spec per feature (template in `CLAUDE.md`)
- `Decisions/`: why we chose things (one note per decision)
- `Tasks/`: task slices per feature, plus the Backlog

## Rules
- Use [[wikilinks]].
- Never delete notes; mark them obsolete.
