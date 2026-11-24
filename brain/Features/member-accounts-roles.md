# Member accounts and roles
Status: done. 2a merged to main 2026-11-25 (merge 13a516f). 2b (branch `feature/phase-2b-filament`) done 2026-11-25, merged to main. Tasks: [[member-accounts-roles-tasks]].
Phase: 2

Goal: Only approved people have accounts. Each account has one role and, for branch staff, one branch. Every branch-scoped record is protected in Policies and query scopes, not only in the UI.

Split (owner decision, 2026-11-24):
- **2a Security core:** backend, auth, roles, policies, tests, themed auth screens.
- **2b Filament admin screens:** Service, GalleryImage, TeamMember, Faq, Branch, User resources.

Users: super-admin, branch-head, branch-admin, member. Visitor = not logged in.

Screens / routes:
- Login, forgot password, reset password (Breeze, themed). `/register` removed (returns 404).
- `/profile` (own info, read-only branch and role).
- `/dashboard` (member home).
- `/admin` (Filament). Only users with an admin role.

2a data changes:
- Migration: `users.branch_id` nullable FK to `branches`, `nullOnDelete`. Not in `$fillable`; set explicitly only.
- Spatie roles seeded: `super-admin`, `branch-head`, `branch-admin`, `member` (role only, no permissions yet).
- Demo seed (local only): 1 super-admin, 1 branch-head and 1 branch-admin per branch, about 3 fake members per branch. Password "password".
- Artisan command `ministry:create-super-admin` creates the real super-admin. See [[super-admin-via-artisan-command]].

2b data changes:
- `branches.facebook_url` nullable. "Our churches" reads from the `branches` table (main first). `ministry.churches` config removed.
- `gallery_images.branch_id` and `team_members.branch_id` stay nullable. Null = global.

Permissions:
- Super-admin: all branches, only role that can assign or remove roles (via `AssignRole`).
- Branch-head: admin of ONE branch. Manages own-branch members and services. Cannot assign roles.
- Branch-admin: applications and duty roster for ONE branch (later phases). Cannot assign roles.
- Member: own profile only. Cannot change own role or branch. Can delete own account, except that the last super-admin cannot delete self.
- `/admin` access: super-admin always; branch-head and branch-admin only with a branch set; others 403.
- Gate::before: super-admin passes all gates.
- Global content (null `branch_id` on gallery and team; all FAQs): only super-admin edits. Branch staff may view.
- Scoping: `BelongsToBranch` trait with `visibleTo(User)` scope on Service, GalleryImage, TeamMember. Enforced in Policies and Filament queries.

Security rules (2a):
- Route throttle on login, forgot password, reset password (in addition to Breeze's login limiter).
- Mass assignment: `branch_id` and `roles` cannot be set from request input.

Tests: see [[member-accounts-roles-tasks]] slice 5.

Out of scope:
- Join applications (Phase 3).
- Permissions table beyond roles (added only when a feature needs them).
- Spatie teams (not used; see [[Backlog]]).
- Open signup, social login, two-factor login.
- Duty roster, notifications (Phases 4 and 5).

Tasks: [[member-accounts-roles-tasks]]
Notes / decisions: [[super-admin-via-artisan-command]], [[phase-order-roles-before-applications]], [[admin-user-management]], [[Roadmap]]
UI polish of `/login` and `/dashboard`: [[login-dashboard-polish]]
