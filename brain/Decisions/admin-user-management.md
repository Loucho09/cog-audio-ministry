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
