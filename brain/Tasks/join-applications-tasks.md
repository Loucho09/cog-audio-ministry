# Tasks: join applications
Feature: [[join-applications]]
Branch: feature/join-applications (in progress)

- [x] Slice 1 (Done 2026-11-25, commit 1d84d6a): data layer (migration, `JoinApplication` model with `$fillable`, casts, branch relation, status constants, factory, policy, branch query scope, seeder with fake applications; tests for branch scoping and mass assignment)
  - Done when: `migrate:fresh --seed` shows fake applications; tests prove a branch-head cannot see another branch's rows and `status`/`user_id` cannot be mass-assigned.
- [x] Slice 2 (Done 2026-11-25, commit 9103609): public form (routes `join.create` / `join.store` / `join.thanks`, controller, `StoreJoinApplicationRequest` with the duplicate-block rule, honeypot, rate limit, Blade form and thank-you page, light/dark, 360px, meta title and description)
  - Done when: tests pass for happy path (saved), honeypot blocks, duplicate email blocked (account or pending), rate limit returns 429; form works at 360px in both themes.
- [x] Slice 3 (Done 2026-11-25, commit 5156e62): queued notification to branch-head(s), branch-admin(s), and super-admin(s) on new application
  - Done when: test shows the mail is queued to exactly those users, and no one from another branch.
- [x] Slice 4 (Done 2026-11-25, commit 3b52520): Filament `JoinApplication` resource (list and view only; branch and status filters; scoped by `visibleTo`; approve and reject actions deferred to Slice 5)
  - Done when: tests show a branch-admin gets 403 or no row for another branch, and approve/reject on a non-pending row is refused.
  - [x] Sub-task (commit 3b52520): replaced `url('/admin')` TODO in `app/Notifications/NewJoinApplication.php` with the Filament view URL.
- [x] Slice 5 (Done 2026-11-25, commit 40e4815): approval and reject actions (in a DB transaction: create member user with `branch_id`, set `status`, `reviewed_by`, `reviewed_at`, `user_id`; queue set-password email)
  - Done when: tests show the user is created with the member role and correct branch, the set-password mail is sent, and a failure rolls back everything.
  - Reminder: approve and reject must check `isPending()` themselves, because `Gate::before` lets super-admin pass. Also hide or disable the action visibility when not pending.
- [x] Slice 6 (Done 2026-11-25, commit 7bc1fad): homepage "Join Us" button points to `join.create`; retire `ministry.join_form_url` and `MINISTRY_JOIN_FORM_URL` (config, `.env.example`, views, tests)
  - Done when: no reference to `join_form_url` remains in code; homepage tests pass; [[join-form-link]] updated by brain-keeper.

- [ ] Slice 7: pending applications widget on `/admin` (dashboard slice 5, see [[dashboards]]): count and latest pending applications for the admin's branch (super-admin: all), link to the Filament list; scoped via the resource's `getEloquentQuery()`; `canView()` for admin roles.
  - Done when: test shows a branch-head sees only own-branch pending counts; a member cannot view the widget.

After all slices: reviewer, fixes, brain-keeper. Then Phase 3 is marked done in [[Roadmap]].
