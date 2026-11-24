# Content seeders use firstOrCreate
Date: 2026-11-24
Status: accepted

Related: [[homepage-sections]], [[Roadmap]], [[00-Index]]

Decision: content seeders (services, FAQs, gallery) use `firstOrCreate`, so re-running a seeder never overwrites admin edits.

Why: content is managed in Filament later. A re-seed must not undo owner changes.

Notes:
- This content is demo or initial content only.
- Phase 7 removes fake data (see [[Roadmap]]).
