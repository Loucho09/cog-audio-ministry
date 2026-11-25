# Duty guest assign tasks
Spec: [[duty-guest-assign]]. Branch: `feature/duty-guest-assign`. Agent: `backend-builder` (slices 1-2), `fullstack` (slice 3).

- [x] Slice 1 (b827c00): Schema + model. Migration (`user_id` nullable, add `guest_name`), `DutyAssignment` fillable + `isGuest()` + `displayName` accessor, factory `guest()` state. Test: migrate fresh, guest row saves, member rows unchanged.
- [x] Slice 2 (edafbd1): Rules (tests first). `DutyRosterService::assignGuest(Event, DutyRole, string $name, ?User $by)`; null-safe `updateEvent` / `assertTimeChangeAllowed`. Tests: auto-confirmed, capacity, started event, unknown slot, name validation, duplicate name at overlapping time, remove guest, time change with guest present.
- [x] Slice 3 (cc29157): Filament. Assign form "Member / Guest" choice, guest name field, table shows "<name> (Guest)". Demo seeder adds one guest. Tests: admin assigns guest, other-branch admin cannot, member mode still works.
- [x] Slice 4 (review fixes cb5d820): `reviewer`, fix, `brain-keeper` (spec done, Roadmap note). Merge on owner OK (pending).
