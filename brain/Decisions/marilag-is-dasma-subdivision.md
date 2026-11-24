# Marilag is the Dasma subdivision, not a branch
Date: 2026-11-25
Status: accepted (owner confirmed)

Source: Audio Head, via Messenger, confirmed 2026-11-25.

Related: [[branches-table-in-phase-1]], [[new-project-brief-adopted]], [[Project-Brief]], [[homepage-hooks]], [[Roadmap]], [[00-Index]]

Decision: COG Dasmariñas and Marilag are the same church. Marilag is the subdivision where COG Dasmariñas is. It is not a separate branch.

Branches are now 4:
- COG Dasmariñas (slug `dasma`, is_main = true). Address: "Marilag Subdivision, Dasmariñas". Rest of address TODO.
- COG Trece
- COG Silang
- COG Jabez

What changed:
- `BranchSeeder` no longer seeds a Marilag row. Dasma's `address` is seeded as "Marilag Subdivision, Dasmariñas" (only filled when blank).
- Tests updated: 4 branches, and a test that the marilag slug is not seeded.
- `config/ministry.php` contact address is unchanged (still TODO/null).
- Marilag Facebook link is no longer needed.
- Older notes that list Marilag as a branch are marked obsolete with a pointer here.
