# Remove branch-head role
Status: done (merged 2026-11-26)
Phase: 4 (roles cleanup, owner request 2026-11-26)
Goal: one admin level per branch. The `branch-head` role is removed; every branch is run by its `branch-admin` account(s) with the full powers branch-heads had. Max 1 branch-admin per branch, 2 for COG Dasma (main), enforced.
Users: super-admin (assigns roles), branch-admin, member

Roles after the change:
- `super-admin`: all branches; only role that assigns roles and creates branches (unchanged).
- `branch-admin`: everything a branch-head could do in ONE branch: members (edit name, temporary password), services, gallery, team, duty roles, events, roster, join applications. Cannot assign roles. Cannot edit other admins or super-admins.
- `member`: unchanged.

Screens / routes: no new screens. Users → role select loses "branch-head". Clear error when the admin limit is reached.

Data changes:
- Migration: every user with `branch-head` becomes `branch-admin`, then the `branch-head` role is deleted (Spatie tables only; no column changes). Down: recreate the role (users stay branch-admin).
- `config/ministry.php`: `branch_admin_limit` default 1, per-branch overrides by slug: `dasma => 2`.
- Demo accounts: `head@cogdasma.audio` becomes Dasma's 2nd branch-admin (same email, password `password`); `head@cogtrece/silang/jabez.audio` removed. Owner approved 2026-11-26. See [[Demo-Accounts]].

Permissions / rules (tests first):
- Assigning `branch-admin` beyond the branch limit fails (locked check in `AssignRole`), message names the limit.
- Moving a branch-admin to a branch that is full fails.
- Branch-admin can do every former branch-head action in own branch; still blocked across branches.
- Branch-admin cannot assign roles, edit admins/super-admins, or change own role.
- Join application emails go to the branch's branch-admins + super-admin.

Out of scope: homepage gallery/team per branch (parked, Backlog), new admin screens, renaming roles in UI beyond the select.

Tasks: [[remove-branch-head-tasks]]
Notes / decisions: [[remove-branch-head-decision]], [[admins-equal-per-branch]] (superseded in part), [[admin-user-management]] (branch-admin read-only rule superseded)
