# Duty notifications
Status: in progress
Phase: 5 (v1; announcements are Phase 5 v2, see [[Backlog]])
Goal: members hear about their duties without checking the site: in-app (bell) + queued email for new assignments and swap events; reminders before each duty.
Users: member (receives); branch-admin and super-admin (trigger by assigning; receive "claimed swap needs approval" because they approve swaps, see [[duty-swaps]]).

## Owner decisions (2026-11-26)
- v1 = duty notifications only. Announcements move to Phase 5 v2 (separate feature, later).
- Reminder offsets live in `config/ministry.php`. Defaults: 24 hours and 2 hours before start.
- Members get a simple on/off "Email me notifications" toggle on their profile. The in-app bell always stays on.

## Assignment notification
- Content: duty role, event name, date/time, place (branch address if filled, else branch name), satellite branch if relevant.
- Guest assignments (`guest_name`, no user) get no notification.
- Guest names must be escaped in emails (see [[duty-guest-assign]]).
- Channels: database (bell) always; mail only when `email_notifications` is true. Mail is queued.

## Swap notifications (flow from [[duty-swaps]])
Flow: offerer offers (open) -> any eligible member claims -> branch-admin approves or rejects. Offerer can cancel while open or claimed.
- Claimed: tell the branch's admins (and super-admin) a swap needs approval. Tell the offerer someone claimed.
- Approved: tell the offerer (released) and the claimer (new duty).
- Rejected: tell the offerer (still on duty) and the claimer.
- Open offer: no blast to the branch in v1 (they see it under Open swaps on the dashboard). Revisit in Backlog if wanted.
- Recipients confirmed by owner 2026-11-26: claim -> branch admins + offerer; approve/reject -> offerer + claimer; open offer -> nobody.

## Reminders
- Only for `accepted` duties with a user. Never twice.
- Offsets from config (default 24h and 2h before start).
- Scheduled command, runs every few minutes via the scheduler.
- Swapped, declined, expired-swap and guest rows are skipped.

## Screens / routes
- Navbar bell with unread count.
- Notifications list page (own notifications only).
- Mark one read, mark all read.
- Profile page: "Email me notifications" switch.

## Data changes
- `notifications` table (Laravel built-in).
- `users.email_notifications` boolean, default true.
- `duty_assignments.reminded_day_at` and `reminded_soon_at`, nullable timestamps. Names may change in build; record final names when done.
- No existing data changes.

## Permissions
- A user sees and marks only their own notifications.
- Members cannot set other users' `email_notifications`.

## Out of scope
- Announcements (Phase 5 v2).
- SMS and push.
- Per-type email preferences (Backlog).
- Digest emails.

Tasks: [[duty-notifications-tasks]]
Notes / decisions: [[duty-roster]], [[duty-swaps]], [[duty-guest-assign]], [[Roadmap]]

## Final names
Slice 1 done 2026-11-26 (commits 5e684c6, d1c66ea). See [[duty-notifications-tasks]].
- Migrations: `2026_11_26_200801_create_notifications_table`, `2026_11_26_200802_add_email_notifications_to_users_table`.
- `users.email_notifications` boolean default true, cast bool, NOT in `$fillable` yet (slice 5 adds the toggle).
- Notification: `App\Notifications\DutyAssigned` (queued, afterCommit; database always, mail when `email_notifications` true or null).
- Trigger: `DutyRosterService::assign()` after commit, new param `bool $notify = true`. Seeder passes `notify: false`. `assignGuest` and swap approve do not notify.
- Database payload keys: `assignment_id`, `event_id`, `role`, `event_title`, `starts_at` (ISO 8601), `place`, `message`.
- Reminder column names: TODO (slice 3).
- Production needs a running queue worker for emails (see Phase 7 checklist in [[Roadmap]]).
