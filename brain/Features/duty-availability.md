# Duty availability
Status: planned
Phase: 4 (v2a of the duty roster; v2b = [[duty-swaps]])
Goal: members mark dates and service blocks (AM / PM / whole day) when they can't serve, so admins stop assigning them and stop chasing replacements in Messenger.
Users: member, branch-admin (as a member for their own availability; as admin when assigning), super-admin

## AM / PM blocks
- An event's block comes from its start time (app timezone): before the cutoff = **AM**, at or after = **PM**.
- Cutoff lives in `config/ministry.php` as `duty_pm_starts_at`, default `'15:00'`.
- Why 15:00: Sunday AM services are 7:30, 9:30, 11:30, 1:30 PM; PM services are 3:30, 5:30, 7:30 PM (owner, 2026-11-26). Other days' services use the same cutoff.
- Duty role names (e.g. "Wingman", "Mid service") are branch duty roles managed in Filament (v1). Not part of this feature.

## Screens / routes
- Member dashboard, new section **"My availability"**:
  - Form: date + "Can't serve": Whole day / AM / PM, optional note (max 100 chars).
  - List of upcoming marked dates with a Remove button.
- Routes (auth): `POST /availability` (`availability.store`), `DELETE /availability/{unavailability}` (`availability.destroy`).
- Filament, Events → event → Assignments → **Assign**: members unavailable for that event's block show "(unavailable)" in the dropdown and can't be picked.

## Data changes
- New table `member_unavailabilities`: `id`, `user_id` (cascade), `branch_id` (user's branch when marked, for scoping), `date`, `part` (`whole_day` | `am` | `pm`, backed enum `AvailabilityPart`), `note` string(100) nullable, timestamps.
- Unique `user_id + date` (one entry per day; changing AM to Whole day = remove then add).
- No change to existing tables or data.

## Rules (Pest tests first, in `DutyRosterService` / new `AvailabilityService`)
- `assign()` rejects a member who is unavailable for the event's date and block (whole day blocks both).
- Date must be today or later, at most 6 months ahead. *(my call)*
- Marking is blocked when the member already has an active duty (assigned/accepted) in that block: "You're on duty then. Offer a swap or ask your admin." *(my call; keeps the roster honest)*
- Removing a past entry is not allowed (past rows stay as history).
- Guests are not affected (no account).
- Swaps (v2b): a member can't claim a duty in a block they marked unavailable.

## Permissions
- Members create/remove only their own entries (Policy `MemberUnavailabilityPolicy`, owner only).
- Admins never edit a member's availability. They only see it through the Assign dropdown.
- Branch scoping: the Assign dropdown only checks members of the event's branch (unchanged v1 rule).

## Out of scope
- Recurring availability ("every first Sunday").
- An admin list/calendar of who is unavailable → Backlog.
- Notifying admins when someone marks unavailable → Phase 5.
- Changing duties already assigned when a member marks a date (blocked instead, see rules).

Tasks: [[duty-availability-tasks]] (to be written after spec approval)
Notes / decisions: [[duty-roster]], [[duty-swaps]]
