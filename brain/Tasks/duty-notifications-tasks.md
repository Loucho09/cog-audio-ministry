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
- [ ] 3. Reminders
  - Config offsets in `config/ministry.php` (24h, 2h)
  - Scheduled command, sent-at columns on `duty_assignments`
  - Accepted duties with a user only; no duplicates
  - Tests
- [ ] 4. Bell UI
  - Navbar bell + unread count
  - List page, mark read, mark all read (own only)
  - Tests
- [ ] 5. Profile email toggle
  - Switch on profile page
  - Tests
