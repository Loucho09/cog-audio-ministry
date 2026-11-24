# Homepage hooks
Status: done (pending owner browser check at 360px and dark mode)
Phase: 1

Related: [[00-Index]], [[Roadmap]], [[homepage-sections]], [[churches-list-in-config]], [[owner-wording-our-churches]]

Goal: visitors should feel how important the audio ministry is, want to join, and come back for updates. Owner request 2026-11-24.

Users: visitor

Screens / routes:
- `/` only (`home`, served by `HomeController`).
- Section order: hero, impact strip, about, what we do, "One sound, many churches" (churches), meet the team (flag), upcoming services, gallery, join us, stay updated, contact, footer. FAQ hidden from the page.

Changes (built):
1. Hide FAQ from the homepage: `<x-home.faq>` removed from the welcome view and the FAQ query removed from `HomeController`. `Faq` model, table, seeder, factory and component kept (may return later).
2. Impact strip right after the hero:
   - Churches served: 5 (COG Dasmariñas main + 4 of our churches), from config.
   - Services per week: counted from the `services` table (active rows).
   - Years serving: shown only when `MINISTRY_YEARS_SERVING` is set. TODO owner number. No invented numbers.
3. "One sound, many churches" section after what we do: COG Dasmariñas (main) plus Marilag, Trece, Silang, Jabez. Church logo `public/images/cog-church-logo.png`. Short "behind the sound" story (sound and mixing so every sermon and song is heard clearly). Names only; addresses TODO. List from `config/ministry.php` (see [[churches-list-in-config]]).
4. "Stay updated" section before contact: Follow on Facebook (`ministry.facebook_url`). Messenger (`ministry.messenger_url`) shows only when set.

Wording:
- Visitor-facing copy says "our churches", never "satellite" (owner decision 2026-11-24, see [[owner-wording-our-churches]]). Earlier "satellite" wording in this spec is superseded.

Data changes:
- None. No migrations.
- Config: `churches` list in `config/ministry.php`.

Permissions:
- Public, read-only.

Requirements:
- Works in light and dark mode and at 360px.
- Copy warm, simple, Tagalog-friendly; owner-confirm copy marked as pending.

Remaining TODOs:
- Years serving number (`MINISTRY_YEARS_SERVING`)
- Messenger link (`ministry.messenger_url`)
- Audio Ministry logo `public/images/logo.png` (navbar)
- Church addresses
- Owner review of "behind the sound" copy
- Owner browser check at 360px and dark mode

Out of scope:
- Public updates or news feed (parked in [[Backlog]] for Phase 5).
- Testimonies (parked in [[Backlog]]).
- Sticky join CTA (parked in [[Backlog]]).
- "No experience needed" steps (parked in [[Backlog]]; owner declined for now).
- Deleting FAQ code.
- Church addresses and details (TODO).

Tasks: [[homepage-hooks-tasks]]
Notes / decisions: [[churches-list-in-config]], [[owner-wording-our-churches]], [[section-backdrops]], [[stage-palette]]
