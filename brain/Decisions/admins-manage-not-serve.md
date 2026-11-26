# Admins manage, not serve
Date: 2026-11-26
Related: [[admin-dashboard]], [[duty-roster]], [[duty-swaps]], [[duty-availability]]

## Decision
- Branch-admins and the super-admin only manage. They do not take duties.
- Admins are removed from the Assign member list in Filament.
- `DutyRosterService` refuses to assign an admin (server rule).
- Admins cannot claim swaps or mark availability (policies).
- The demo seeder no longer gives admins duties.
- Existing admin assignments in local data stay but are hidden. `migrate:fresh --seed` clears them.

## Why
- Owner choice, 2026-11-26.
- Clean split: admins get an admin dashboard, members get the member dashboard. Each view has one purpose.
- Admins already run the roster, so serving on it would mix the two roles and skew the "unfilled" and "waiting" counts.

## Effects
- Admin dashboard: see [[admin-dashboard]].
- Member dashboard is unchanged.
