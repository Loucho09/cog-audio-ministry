# Duty availability decision
Date: 2026-11-26
Related: [[duty-availability]], [[duty-swaps]], [[duty-roster]]

## AM / PM blocks with a 15:00 cutoff
- Members mark "Whole day", "AM" or "PM" instead of exact service times.
- Sunday AM services are 7:30, 9:30, 11:30 and 1:30 PM. PM services are 3:30, 5:30 and 7:30 PM (owner). One cutoff at 15:00 splits them cleanly.
- The cutoff is `duty_pm_starts_at` in `config/ministry.php` (default `'15:00'`), so it can change without code. Other days use the same cutoff.

## Open-offer swaps (owner, 2026-11-26)
- A member who can't serve posts the duty as an open offer. Any eligible member can claim it. A branch-admin approves.
- Chosen by the owner, so members don't have to chase one named person in Messenger.

## Self-approval rule
- In a branch with two admins, the admin who offered or claimed the swap can't approve it. The other admin approves.
- A single-admin branch may self-approve, since no one else can.
- Super-admin can always approve.
