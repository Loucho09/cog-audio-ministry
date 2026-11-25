# Duty guest assign
Status: done (merged 2026-11-26)
Phase: 4
Goal: when assigning a duty, the admin either picks an existing member of the event's branch (dropdown, as in v1) or types a guest name by hand for someone without an account (visitor, volunteer from another church).
Users: branch-head, branch-admin (own branch), super-admin (any branch)

Screens / routes:
- `/admin` → Events → event → Assignments → **Assign**: new choice "Member" / "Guest (type a name)". Member = searchable dropdown (unchanged). Guest = text field "Guest name".
- Assignments table: guest rows show "<name> (Guest)" and status "Confirmed".
- No public or member dashboard change (guests have no account).

Data changes:
- `duty_assignments.user_id` becomes nullable (existing rows untouched).
- New column `duty_assignments.guest_name` string(100), nullable.
- Exactly one of `user_id` / `guest_name` is set (enforced in `DutyRosterService`, tested).

Rules (Pest tests first):
- Guest is auto-confirmed: status `accepted`, `responded_at` = now. Owner decision 2026-11-26.
- Guest fills a slot and counts toward capacity; full slot blocks a guest.
- No guest on a started event; role must be a slot of the event.
- Guest name trimmed, 2-100 chars, required in guest mode.
- Same guest name (case-insensitive) cannot be assigned twice at overlapping times in the same branch.
- Remove works for guests like members (blocked after the event starts).
- Event time/slot edits ignore guest rows in the member overlap check (no crash on null `user_id`).

Permissions: same as v1 Assign (event's branch only, enforced in the service and the relation manager). Members never see guest rows except through event totals.

Out of scope: guest phone/email, saved guest list, reminders/notifications to guests, guests in swaps (v2), member dashboard changes.

Tasks: [[duty-guest-assign-tasks]]
Notes / decisions: [[duty-roster]], [[Demo-Accounts]], [[duty-guest-assign]] (decision note)

## Notes
- Guest auto-confirmed (owner decision 2026-11-26).
- Branch row locked on guest assign to stop a duplicate-name race.
- Name match is case-insensitive and accent-insensitive on MySQL (José = Jose). Intended.
- Migration rollback deletes all guest rows (rows with `user_id` null).
- Demo seeder adds one "Guest Volunteer" per branch.
