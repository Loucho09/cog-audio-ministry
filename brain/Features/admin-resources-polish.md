# Admin resources polish
Status: done (merged to main 2026-11-25)
Phase: add-on before Phase 3 (see [[Roadmap]])

Goal: restyle the Filament Team, Services, Gallery and Branches resources to match the Users table redesign (commit f20bccd, which was never recorded in the brain before this note). Easier to scan on a phone, clearer counts, safer edits.

Branch: `feature/admin-resources-polish` (merged to main 2026-11-25, e628b46).
Commits: f0312eb (FAQ removed, see [[faq-removed]]), c518822 (Team), bf50282 (Services), 75add68 (Gallery), 609fd74 (Branches), plus a follow-up test commit. 406 tests pass.

Users: super-admin, branch-head, branch-admin (staff).

Screens / routes:
- `/admin` Filament resources: Team members, Services, Gallery images, Branches.

Changes:
- Users table redesign (f20bccd), used as the pattern: Split/Stack responsive rows (stacked on phone, split on desktop).
- Badges for status.
- Tabs with branch-scoped counts:
  - Team: All / Featured / Full-time / Pending approval.
  - Services: All / Active / Inactive, plus day and active filters. Status badge: Inactive, Ended, Scheduled, Active.
  - Gallery: All / Published / Pending approval.
- Empty states.
- Sectioned forms and edit summary cards.
- Branches: Main badge, address and contact icons, member and service counts via `withCount`.
- Shared helper: `app/Filament/Support/PublishStatus.php` (status labels and badge colours).

Data changes: none.

Permissions:
- "Pending approval" tab is visible to all staff, branch-scoped and read-only.
- Filter and publish toggle remain super-admin only. Reviewer approved this split.
- All queries stay scoped in the resource `getEloquentQuery()`, not only in the view.

Out of scope:
- Browser check at 360px and dark mode (still open).
- Combined count query for tabs (parked in [[Backlog]]).
- FAQ resource (removed, see [[faq-removed]]).

Tasks: none separate (slices were done as commits on the branch).
Notes / decisions: [[dashboards]], [[filament-widget-styling]], [[member-accounts-roles]], [[faq-removed]]

Follow-ups: gallery upload and return-to-list fix, branch `fix/gallery-upload` (not merged yet): [[gallery-upload-fix]]
