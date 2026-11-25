# Dashboards (member + admin)
Status: done for slices 1-4 (merged 2026-11-25); slice 5 during Phase 3
Phase: 2 add-on (finishes the missing Slice 2 of [[login-dashboard-polish]]; owner chose to do this before Phase 3, 2026-11-25). Slice 5 is built during Phase 3.

Goal: Give every logged-in user a useful home screen. Members see their branch and upcoming services. Admins see the state of their branch at a glance. The super-admin sees all branches and what content is still missing before launch.

Users: member, branch-admin, branch-head, super-admin.

Finding (2026-11-25): the brain said [[login-dashboard-polish]] Slice 2 was merged, but `resources/views/dashboard.blade.php` is still the placeholder and there is no `DashboardTest`. This spec replaces that slice.

Screens / routes:
- `/dashboard` (member home, Blade). Route moves from a closure to a `DashboardController`.
- `/admin` (Filament dashboard), with custom widgets in `app/Filament/Widgets/`. The stock `FilamentInfoWidget` is removed; `AccountWidget` stays.

Slice 1, member dashboard (`/dashboard`):
- Time-of-day greeting (Asia/Manila).
- Branch card: name, plus address, contact and Facebook link when they are filled in. Shows a friendly empty state when they are not.
- Upcoming services from all branches, own branch first, labelled by branch.
- Quick links: profile, change password, and "Admin panel" for admin roles only. The admin check uses the same server rule as `canAccessPanel`.
- "Coming soon" cards for Duties (Phase 4) and Announcements (Phase 5).
- Light and dark mode, 360px check.

Slice 2, admin stats widget:
- Stat cards: members, services, gallery photos (published vs draft), team members (published vs draft).
- Branch-head and branch-admin see counts for their own branch only. The super-admin sees all branches.

Slice 3, super-admin overview:
- Branch table: branch, member count, branch head(s), branch admins count. Branches with no head are flagged.
- "Site setup" checklist (reads `config/ministry.php` and the database): logo file present, church address, Messenger link, years serving, branches missing an address, no published gallery photos, no featured team members. Each item is a done/missing row with a hint on where to fix it.
- Super-admin only.

Slice 4, accounts needing attention:
- Table of accounts in the admin's branch (super-admin: all) that still have `must_change_password = true`, plus accounts with no branch (super-admin only).
- The 5 most recently created accounts in scope.

Slice 5, pending applications widget (built during Phase 3):
- Count and latest pending join applications for the admin's branch (super-admin: all), linking to the Filament applications list.

Data changes: none. Reads `users`, `branches`, `services`, `gallery_images`, `team_members`, roles, and config. Slice 5 reads the Phase 3 `join_applications` table.

Permissions:
- `/dashboard`: `auth` + `verified` (unchanged).
- Widgets use `canView()`: slices 2 and 4 need an admin role, slice 3 needs super-admin.
- All widget queries are scoped on the server (same branch rules as the resources, e.g. `visibleTo`). Scoping is never done in the view only.

Tests:
- `DashboardTest`: guest redirected; services from all branches with own branch first; admin link hidden for members and shown for admins.
- Widget tests: branch-head counts exclude other branches; super-admin sees totals; members and branch admins cannot view the super-admin widgets; the attention list never shows another branch's users.

Out of scope:
- Duties, swaps, announcements (Phases 4 and 5).
- Charts or graphs (no new packages).
- Any schema change, or any change to roles or auth.

Progress (2026-11-25):
- Slice 1, member dashboard: `DashboardController`; greeting by `config('app.timezone')`; branch card with https-only Facebook link (`safeFacebookUrl`); services from all branches, own branch first (`UpcomingServices::fromServices` on eager-loaded services, no N+1); quick links, including the admin panel link via `User::hasAdminAccess()` (`canAccessPanel` now delegates to it); coming-soon cards.
- Slice 2, `BranchStatsWidget`: counts come from each resource's `getEloquentQuery()` so numbers match the lists; gallery and team staff counts include all-churches rows; `FilamentInfoWidget` removed.
- Slice 3, `BranchOverviewWidget` and `SiteSetupWidget` (uses `App\Support\SiteSetupChecklist`), super-admin only; `mount()` aborts 403 otherwise. The site-setup view uses only Filament components because the panel has no custom theme (arbitrary Tailwind classes do not render in `/admin`). See [[filament-widget-styling]].
- Slice 4, `AccountsAttentionWidget` (temp password; no-branch rows super-admin only) and `RecentAccountsWidget` (5 newest), scoped via `UserResource::getEloquentQuery()`.
- Reviewer run: no scoping leaks found. Fixes applied in commit f3b29d1.
- Commits on `feature/dashboards` (not yet merged to main): 9de09f8, b05fd24, df6120b, 63eb51c, f3b29d1.
- Tests: 369 total, 368 pass, 1 pre-existing skip.
- Browser-checked with Playwright 2026-11-25 (360px, light + dark, `/admin` super-admin). Merged to main.
- Parked items: see [[Backlog]].

Tasks: [[dashboards-tasks]]
Notes / decisions: [[login-dashboard-polish]], [[member-accounts-roles]], [[join-applications]], [[filament-widget-styling]], [[Roadmap]]
