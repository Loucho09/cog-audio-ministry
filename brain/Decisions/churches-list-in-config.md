# Churches list in config for now
Status: accepted (2026-11-24)

Related: [[homepage-hooks]], [[branches-table-in-phase-1]], [[second-brief-merged]], [[Roadmap]]

Decision: the list of churches shown on the homepage ("One sound, many churches" and the impact strip) lives in `config/ministry.php` under `churches`.

Why:
- The `branches` table already exists from Phase 1 ([[branches-table-in-phase-1]]), but branch roles and branch management come in Phase 2.
- Adding public display fields to `branches` now would be a schema change in Phase 1. Avoid it.
- Config is simple, version-controlled, and needs no migration. The homepage only shows names.

Plan:
- Phase 2: move the display list to the `branches` table (name, slug, is_main). Seed the 5 branches there.
- Then read the homepage list from the `branches` table and remove the `churches` config key.
- Addresses and contact fields stay TODO until the owner supplies them.

Consequences:
- Adding a church before Phase 2 means editing config, not the database.
- The impact strip count of churches comes from config until the move.
