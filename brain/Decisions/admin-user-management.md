# Admin user management (Phase 2b)
Date: 2026-11-25
Status: approved by owner

Related: [[member-accounts-roles]], [[super-admin-via-artisan-command]], [[phase-order-roles-before-applications]]

## Decision
(a) Super-admin can create accounts by hand at `/admin` before Phase 3 join applications exist. New users get a set-password email. The admin never types a password.
(b) Branch-head may edit only name and email of own-branch members. Branch and role are super-admin only.

## Why
- Phase 2 comes before Phase 3, so accounts need a manual path until applications exist.
- No passwords typed by admins: avoids weak or shared passwords.
- Branch-heads manage people but cannot escalate roles or move members between branches.

## Rules
- Role changes go only through `AssignRole` (super-admin only).
- Branch field is set only by super-admin.
- Branch-admin stays read-only on own-branch members.

## Owner decisions (2026-11-25)
- Only the super-admin can delete users. `UserPolicy::delete` returns false; the super-admin passes via `Gate::before`.
- Only the super-admin can change a user's email. Branch-heads edit the name only, because an email change plus password reset would allow account takeover.
- The super-admin gets a "Resend set-password email" action. If the mail send fails, the super-admin sees a warning instead of a crash.
- Branch-admins see the member list only, with no view page.
- Branch-heads can edit only plain members of their own branch, not other heads or admins.

Related: [[member-accounts-roles-tasks]], [[Backlog]]

> **Update 2026-11-26:** the `branch-head` role was removed. Branch-admins now have the full branch powers described here (including editing own-branch members). See [[remove-branch-head-decision]].
