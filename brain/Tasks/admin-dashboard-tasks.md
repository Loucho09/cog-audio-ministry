# Admin dashboard tasks
Status: done (2026-11-26)
Related: [[admin-dashboard]], [[admins-manage-not-serve]], [[Roadmap]]

Slices (each about 5 files or fewer; build, review, fix, record):

- [x] **A. Backend rule: admins only manage**
  - `DutyRosterService` refuses to assign admins (server rule).
  - Policies: admins cannot claim swaps or mark availability.
  - Filament Assign member list excludes admin accounts.
  - Tests written first: admin cannot be assigned, claim, or mark availability.
  - Existing admin assignments stay in data but are hidden.

- [x] **B. Admin dashboard data**
  - Controller or action builds the admin cards, branch-scoped.
  - Super-admin gets one row per branch.
  - Tests: admin sees admin view; member sees member view; branch-admin never sees other branch counts.

- [x] **C. `dashboard-admin` Blade view**
  - Cards: Needs attention, Unfilled slots, Waiting for reply, Who's unavailable, Branch snapshot, Quick actions.
  - Light and dark mode, 360px check.
  - Uses existing dashboard components where possible.

- [x] **D. Seeder, review, brain**
  - Demo seeder gives admins no duties; `migrate:fresh --seed` clears old admin assignments.
  - `reviewer` run; fix issues.
  - Update [[admin-dashboard]] status, [[Roadmap]], and [[00-Index]].
