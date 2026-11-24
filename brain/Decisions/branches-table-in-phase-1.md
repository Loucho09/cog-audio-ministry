# Branches table in Phase 1
Date: 2026-11-24
Status: accepted (owner approved)

Related: [[homepage-sections]], [[Roadmap]], [[Project-Brief]], [[00-Index]]

Decision: create the `branches` table in Phase 1, not Phase 2.

Why: `services` needs a `branch_id`, and the homepage schedule reads from it.

Scope:
- No roles or auth changes. Spatie roles, Breeze, and branch scoping stay in Phase 2.
- Branch names keep the "COG" prefix:
  - COG Dasmariñas (slug `dasma`, is_main = true)
  - COG Marilag
  - COG Trece
  - COG Silang
  - COG Jabez
- `is_main` is not mass assignable. Set it explicitly in seeders and code.
