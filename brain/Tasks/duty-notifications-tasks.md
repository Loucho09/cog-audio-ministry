# Duty notifications tasks
Spec: [[duty-notifications]]. Branch: `feature/duty-notifications`. Related: [[Roadmap]], [[Backlog]]

Loop per slice: build, test, review, commit.

- [x] 1. Base + assignment notification. DONE 2026-11-26
  - Commits 5e684c6 (feature), d1c66ea (review fixes), branch `feature/duty-notifications`
  - 929 tests (928 pass, 1 skipped). Reviewer: no must-fix; should-fix applied (migration renamed, deleteWhenMissingModels, notify failure reported not thrown, null-safe branch, extra tests incl. Filament assign path and real send)
  - `notifications` table, `users.email_notifications` column (default true)
  - `DutyAssigned` notification, mail + database, queued
  - Guests skipped; toggle respected (mail off, bell still on)
  - Guest names escaped
  - Tests
- [x] 2. Swap notifications. DONE 2026-11-26
  - Commits 89d7b8d (feature), fdae051 (review fixes)
  - 942 tests (941 pass, 1 skipped). Reviewer: no must-fix; fixes applied (per-recipient try/catch, stale claim skipped via shouldSend when swap no longer Claimed, null-safe details, eager-load once)
  - `DutySwapUpdated(DutySwap $swap, string $type)`: swap_claimed / swap_approved / swap_rejected (see [[duty-swaps]])
  - Hooked in `DutySwapService::claim/approve/reject` via private `notifySwap()` after commit
  - Recipients: claim = branch-admins + super-admins + offerer; approve/reject = offerer + claimer (acting admin excluded); reject of open swap = offerer only; open offer = nobody
  - Tests
- [x] 3. Reminders. DONE 2026-11-26
  - Commits 6f51de3 (feature), 7e88f78 (review fixes)
  - Reviewer found 2 must-fix (claim UPDATE did not re-check status; no shouldSend for late queued jobs), both fixed
  - Config offsets in `config/ministry.php` (24h, 2h)
  - Scheduled command, sent-at columns on `duty_assignments`
  - Accepted duties with a user only; no duplicates
  - Tests
  - See [[reminder-delivery-at-most-once]]
- [x] 4. Bell UI. DONE 2026-11-26
  - Commits e489e53 (feature), 149f8b5 (review fixes; mobile header fits at 320/360px)
  - No must-fix; fixed open-redirect hardening, a11y, dark badge contrast
  - Navbar bell + unread count
  - List page, mark read, mark all read (own only)
  - Tests
- [x] 5. Profile email toggle. DONE 2026-11-26
  - Commits ff9cfc9 (feature), c08259b (review fixes)
  - Own throttle 30/min instead of password-change bucket; role=switch; 3:1 off track; live status
  - Switch on profile page
  - Tests
- Extra (owner asked for UI/UX fixes 2026-11-26): d84d7b9 fixed a pre-Phase-5 bug. Secondary-button forced `type="button"`, so Decline, Offer swap, Cancel swap and availability Remove never submitted. An apostrophe in the decline confirm text also broke that form's Blade parsing (no CSRF). Found by a UI audit; tests now render the real HTML. Dashboard UX polish slice in progress (record later).
