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

## Features
- [[public-layout]]: navbar, footer, theme tokens, placeholder home page (Phase 1, done)
- [[homepage-sections]]: homepage sections as Blade components (Phase 1, in progress; tasks in [[homepage-sections-tasks]])
- [[dark-mode]]: light/dark theme toggle on public pages (Phase 1, done pending owner check; added 2026-11-24)

## Folders
- `Features/`: one spec per feature (template in `CLAUDE.md`)
- `Decisions/`: why we chose things (one note per decision)
- `Tasks/`: task slices per feature, plus the Backlog

## Rules
- Use [[wikilinks]].
- Never delete notes; mark them obsolete.
