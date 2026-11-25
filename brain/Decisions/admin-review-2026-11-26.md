# Full admin review 2026-11-26
Branch: `fix/admin-review`. Owner asked to review and fix every admin page (Dashboard, Branches, Gallery, Services, Team, Users, Join applications, Duty roles, Events).

Result: no cross-branch leaks or role escalation found. Fixed:
- Branches: delete blocked when applications, duty roles or duties exist; unique name; slug read-only on edit; menu hidden from branch staff; counts say "Accounts".
- Users: move blocked while the user has upcoming duties; super-admin cannot change own role/branch; one shared "needs attention" scope with reasons; temporary-password sets logged (no password).
- Events: duplicate service + start time shows a message, not a 500; edits re-check "started" under lock and refuse a past start; past list newest first; Assign/Remove check `update`.
- Duty roles: "Used on" column, clear bulk-delete message, warning when deactivating a role on upcoming events. Roster service rejects non-roster users and roles from other branches.
- Join applications: one grouped count query; "Account exists" badge.
- Gallery/Team: approval note and "Saved. Waiting for approval."; "All churches (view only)" badge; helper text; team photos resized to 400x400 on upload.
- Services: sort order defaults to 0; delete explains what happens to events; duplicate day/time shows a message.

Owner decisions: remove the branch-head role ([[remove-branch-head-decision]]); homepage per-branch content decided later ([[Backlog]]).
