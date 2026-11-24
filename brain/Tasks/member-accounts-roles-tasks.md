# Member accounts and roles: tasks

Related: [[member-accounts-roles]], [[Roadmap]], [[Backlog]]

Branch name for 2a: `feature/phase-2a-roles`. One commit per slice. Run `php artisan test` and `./vendor/bin/pint` before each commit.

## Phase 2a: Security core (done 2026-11-25, pending merge)

- [x] **Slice 1: users.branch_id and factory states**
  - Migration `add_branch_id_to_users_table` (nullable FK, `nullOnDelete`)
  - `User::branch()` and `Branch::users()` relations
  - `UserFactory` states: `superAdmin()`, `branchHead(Branch)`, `branchAdmin(Branch)`, `member(Branch)`
- [x] **Slice 2: roles, demo seed, super-admin command**
  - `RoleSeeder` (4 roles, `findOrCreate`, idempotent)
  - `DemoUserSeeder` replaces `test@example.com`; called from `DatabaseSeeder` after `BranchSeeder`
  - `ministry:create-super-admin` command (`app/Console/Commands/CreateSuperAdmin.php`)
- [x] **Slice 3: lock down auth**
  - Remove register routes and `auth/register.blade.php`; replace `RegistrationTest` with "register returns 404"
  - `throttle:6,1` on POST login, forgot password, reset password
  - Self-delete kept; last super-admin cannot delete self
- [x] **Slice 4: authorization core**
  - `User::canAccessPanel()` rules
  - `Gate::before` in `AppServiceProvider` (super-admin passes all)
  - `BelongsToBranch` trait with `visibleTo(User)` on Service, GalleryImage, TeamMember
  - `app/Actions/AssignRole.php`: only super-admin; throws `AuthorizationException` otherwise
  - `ServicePolicy`, `UserPolicy`
  - Profile page shows branch name and role (read-only)
- [x] **Slice 5: tests (`tests/Feature/Roles/`)**
  - Role escalation: member PATCH `/profile` with `roles` or `branch_id` changes nothing
  - Mass assignment ignores `branch_id`
  - Branch-head and branch-admin calling `AssignRole` are denied; super-admin allowed
  - Cross-branch: Trece branch-head cannot view or update a Dasma service
  - `/admin`: visitor redirected to login; member 403; branch-head, branch-admin, super-admin 200; branch-head with null branch 403
  - Register returns 404; login throttled after limit; create-super-admin command creates a role'd user
- [x] **Slice 6: theme the auth screens (frontend-builder)**
  - `layouts/guest` (login, forgot, reset) and `layouts/app` (dashboard, profile) use theme tokens, Montserrat, logo, light and dark
  - No logic changes
  - Playwright check at 360px, both themes

Done 2026-11-25. Branch `feature/phase-2a-roles`. Commits: 8b54e9b (approval-only auth, throttles, last super-admin guard), 6dafd2b (panel access, branch scope, AssignRole, policies), 3c0cef1 (tests: escalation, cross-branch, panel access), cf371dc (branch-admins view own-branch members, read-only), 48e3e5e (themed auth and member screens), f6b36a4 (reviewer hardening).
Reviewer fixes (f6b36a4): AssignRole locks super-admin rows in a transaction; branch-head and branch-admin need a branch (ValidationException); profile self-delete last-super-admin check in a transaction; DemoUserSeeder only in local/testing; throttle:6,1 on POST confirm-password and PUT password; Admin nav link falls back safely if the Filament panel is missing. Tests: `tests/Feature/Roles/HardeningTest.php`, `RoleEscalationTest.php`.
Result: 185 tests, 184 pass, 1 skipped. Pint clean.

- [x] Merged 2a to main 2026-11-25 (owner go-ahead)

## Phase 2b: Filament admin screens (planned, starts after 2a merges)

- [ ] Filament panel colors and brand name from theme
- [ ] `Service` resource (`starts_on`, `ends_on`, `ends_on >= starts_on`, friendly duplicate error)
- [ ] `GalleryImage` resource (null branch = global, super-admin only edits)
- [ ] `TeamMember` resource (same global rule as gallery)
- [ ] `Faq` resource (global, super-admin only)
- [ ] `Branch` resource (super-admin only); `facebook_url` migration; "Our churches" reads from DB; remove `ministry.churches` config; update `HomepageChurchesTest`
- [ ] `User` resource (super-admin assigns roles via `AssignRole`; branch-head sees own-branch members, no role field)

Detailed slices are written in the 2b spec step.
