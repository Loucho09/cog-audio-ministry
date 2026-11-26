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
- DutyAssigned payload now also has `type` = `duty_assigned`.
- Reminder column names: see Slice 3 below.

### Swap notifications (slice 2 done 2026-11-26, commits 89d7b8d, fdae051)
- Class `App\Notifications\DutySwapUpdated(DutySwap $swap, string $type)`; types `swap_claimed`, `swap_approved`, `swap_rejected`.
- Hooked in `DutySwapService::claim/approve/reject` via private `notifySwap()` after commit. Per-recipient try/catch. `shouldSend` skips a stale claim if the swap is no longer Claimed.
- Recipients as built: claim = branch-admins of the swap's branch + all super-admins + offerer (claimer not notified, own action). Approve/reject = offerer + claimer, acting admin excluded. Reject of open swap = offerer only. Open offer = nobody.
- Super-admins on claim match join applications; owner can drop it (one line in `notifySwap`).
- Approve does NOT also send `DutyAssigned` to the claimer.
- Link: admins go to Filament `DutySwapResource` index; members go to the dashboard.
- Payload keys: `type`, `swap_id`, `assignment_id`, `event_id`, `role`, `event_title`, `starts_at`, `status`, `url`, `message`.
- No user active/suspended flag exists, so no inactive filtering (see [[Backlog]]).
- Slice 4: the bell must render payload `message` with `{{ }}` escaping, never `{!! !!}`.
- Production needs a running queue worker for emails (see Phase 7 checklist in [[Roadmap]]).

### Reminders (slice 3 done 2026-11-26, commits 6f51de3, 7e88f78)
- Config `ministry.reminders` (`day_before_hours` 24, `soon_hours` 2).
- Columns `duty_assignments.reminded_day_at` / `reminded_soon_at` (not fillable). Migrations `2026_11_26_200803` (reminder columns) and `200804` (index on `events.starts_at`).
- `App\Notifications\DutyReminder`: types `duty_reminder_day` / `duty_reminder_soon`. `shouldSend` re-checks Accepted, same user, same future `starts_at`.
- `App\Services\DutyReminderService`: chunkById, atomic conditional claim, claim released if notify throws.
- Command `duty:send-reminders`, scheduled everyFiveMinutes withoutOverlapping in `routes/console.php`. Returns failure if any row failed.
- Accepted duties only. Soon window (now, now+2h] sets both columns; day window (now+2h, now+24h].
- `DutyRosterService::updateEvent` resets both columns when `starts_at` changes (Filament EditEvent goes through it).
- At-most-once delivery after the job is queued. See [[reminder-delivery-at-most-once]].

### Bell (slice 4 done 2026-11-26, commits e489e53, 149f8b5)
- `NotificationController`: index (paginate 20), read (safe same-app redirect), readAll.
- Routes `notifications.index`, `notifications.read`, `notifications.read-all`.
- Components `notification-bell`, `notification-item`, `notification-icon`. View composer on the bell (2 queries max). Messages rendered escaped.

### Profile toggle (slice 5 done 2026-11-26, commits ff9cfc9, c08259b)
- `PATCH /profile/notifications` (`profile.notifications.update`), `UpdateNotificationPreferencesRequest`.
- `ProfileController::updateNotifications` uses `forceFill` (column stays out of `$fillable`).
- Partial `profile/partials/update-notifications-form.blade.php`.

### Production
- Needs the scheduler cron and a queue worker (Phase 7 checklist in [[Roadmap]]).
