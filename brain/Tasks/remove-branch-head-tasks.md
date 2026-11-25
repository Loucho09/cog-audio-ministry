# Remove branch-head tasks
Spec: [[remove-branch-head]]. Branch: `feature/remove-branch-head` (after `fix/admin-review` is merged).

- [ ] Slice 1: Roles + data. Migration (branch-head → branch-admin, delete role), RoleSeeder, UserFactory states, `config/ministry.php` admin limit, `AssignRole` limit check (locked), DemoUserSeeder (Dasma head@ = 2nd admin; other head@ removed). Tests first: limit, move into full branch, migration converts heads.
- [ ] Slice 2: Policies + Filament. Replace every `branch-head` check with `branch-admin` (policies, resources, widgets, AssignmentsRelationManager, DutyRosterService). Update tests that used branch-head.
- [ ] Slice 3: Notifications, member dashboard, other seeders, CLAUDE.md roles section, [[Demo-Accounts]], decision note. Full suite, reviewer, merge on owner OK.
