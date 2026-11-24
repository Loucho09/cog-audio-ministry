# Homepage hooks
Status: planned (awaiting owner go-ahead)
Phase: 1

Related: [[00-Index]], [[Roadmap]], [[homepage-sections]], [[churches-list-in-config]]

Goal: visitors should feel how important the audio ministry is, want to join, and come back for updates. Owner request 2026-11-24.

Users: visitor

Screens / routes:
- `/` only (`home`, served by `HomeController`).
- New section order: hero, impact strip, about, what we do, meet the team (flag), upcoming services, gallery, "One sound, many churches", join us, stay updated, contact, footer. FAQ removed from the page.

Changes:
1. Hide FAQ from the homepage: remove `<x-home.faq>` from the welcome view and the FAQ query from `HomeController`. Keep the `Faq` model, table, seeder, factory and component (not deleted; may return later). Update affected tests.
2. Impact strip right after the hero:
   - Churches served: 5 (COG Dasmariñas main + 4 satellites), from config.
   - Services per week: counted from the `services` table (active rows).
   - Years serving: TODO placeholder until the owner gives the real number. No invented numbers.
3. "One sound, many churches" section: COG Dasmariñas (main) plus satellites Marilag, Trece, Silang, Jabez. Short "behind the sound" story about what the audio team does each service (sound and mixing so every sermon and song is heard clearly). Names only; addresses and details stay TODO. List comes from `config/ministry.php` for now (see [[churches-list-in-config]]).
4. "Stay updated" section before contact: Follow on Facebook (`ministry.facebook_url`) and Messenger (`ministry.messenger_url`, hidden while empty).

Data changes:
- None. No migrations.
- Config: add a `churches` list to `config/ministry.php`.

Permissions:
- Public, read-only.

Requirements:
- Works in light and dark mode and at 360px.
- Copy warm, simple, Tagalog-friendly; owner-confirm copy marked as pending.

Out of scope:
- Public updates or news feed (parked in [[Backlog]] for Phase 5).
- Testimonies (parked in [[Backlog]]).
- Sticky join CTA (parked in [[Backlog]]).
- "No experience needed" steps (parked in [[Backlog]]; owner declined for now).
- Deleting FAQ code.
- Satellite addresses and details (TODO).

Tasks: [[homepage-hooks-tasks]]
Notes / decisions: [[churches-list-in-config]], [[section-backdrops]], [[stage-palette]]
