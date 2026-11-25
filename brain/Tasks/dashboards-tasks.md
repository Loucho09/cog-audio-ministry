# Dashboards tasks
Spec: [[dashboards]]
Branch: `feature/dashboards`

- [x] Slice 1: member dashboard (`DashboardController`, `dashboard.blade.php`, route, `DashboardTest`). Agent: fullstack. Commit 9de09f8.
- [x] Slice 2: admin stats widget (`BranchStatsWidget`, panel provider drops `FilamentInfoWidget`, test). Agent: backend-builder. Commit b05fd24.
- [x] Slice 3: super-admin branch table + site setup checklist (two widgets, tests). Agent: backend-builder. Commit df6120b.
- [x] Slice 4: accounts needing attention + recent accounts widget (widget, test). Agent: backend-builder. Commit 63eb51c.
  - [x] Reviewer run: no scoping leaks. Fixes applied in f3b29d1 (Filament styling, N+1, scoping tests).
- [x] Browser check (Playwright, 2026-11-25): `/dashboard` at 360px light + dark, no horizontal scroll; `/admin` super-admin light + dark. Fixes: service rows stack on mobile; super-admin no longer flagged "No branch" in Accounts needing attention.
- [x] Merge `feature/dashboards` to main (owner OK 2026-11-25).
- [ ] Slice 5 (Phase 3): pending applications widget, built after the `join_applications` table exists. See [[join-applications-tasks]].

Parked items: see [[Backlog]].

After each slice: `php artisan test`, `./vendor/bin/pint`, commit. After slice 4: reviewer, fixes, brain-keeper.
