# Duty Roster (v1)
Status: done (v1, awaiting owner merge)
Phase: 4 (v1 of 2; v2 = swaps + availability, see "Out of scope")
Goal: branch-heads and branch-admins put members on duty for dated events, in fixed slots per duty role; members accept or decline their duties from the dashboard.
Users: member | branch-admin | branch-head | super-admin
Tasks: [[duty-roster-tasks]] (written after the spec is approved)
Notes / decisions: [[Roadmap]] Phase 4; [[login-and-dashboard-decisions]] (members may attend other branches' events)

## Built (branch `feature/duty-roster`, not merged yet)
- Duty roles per branch (Filament, Roster group).
- Service default slots.
- Events: one-off, or "Generate events" from the weekly schedule (max 3 months, skips already-started occurrences).
- Event slots.
- Assignments manager on the event page.
- Read-only View page for started events.
- Member dashboard "My upcoming duties" with Accept/Decline (decline asks for confirmation; buttons hidden once the event starts).
- Demo seed (placeholder roles Duty role A/B/C, local/testing only).

## Decisions made during the build
- Started events are read-only for everyone (no edit, no delete, no assign/remove) to keep history; leaders see them via a View page.
- Branch-heads and branch-admins can also be assigned duties (not only members).
- Deleting a duty role in use, a user with duty history, or a branch with events is refused with a friendly message (deactivate roles instead).
- Super-admin cannot move a duty role to another branch.
- Event edits save atomically through `DutyRosterService::updateEvent` (locks, guards, slot sync).
- Events cannot be created with a start time in the past.
- Owner admin rule: [[admins-equal-per-branch]].

## Known follow-ups
- Editing an upcoming event's start time into the past locks it (needs the future-start rule on edit).
- DutyRole bulk-delete test is weak on FK enforcement.
- Real duty role names and counts still TODO from the owner.
- See [[Backlog]] for parked items.

## Why
Today the roster lives outside the app. Members need one place to see "where am I serving and when", and branch leaders need to see which slots are still empty before a service.

## Decisions made with the owner (2026-11-25)
- Phase 4 is split: **v1** = events, duty roles, slots, assignments, accept/decline, dashboard. **v2** = swaps and availability.
- **Fixed slots per event (option A).** Each event has slots like "Livestream x1"; admins fill them. Chosen over an open list because it shows empty slots and gives v2's "a swap cannot leave an event without its required duties" rule something to check.
- **Duty roles are placeholders for now.** Real role names and counts come from the owner later; they are data in Filament, not code.

## Builder's calls (Claude's proposals, correct if wrong)
- Events are a new table, separate from `services` (which is a weekly schedule). Events are generated from services for a date range, or created one-off (e.g. Revival Night).
- Duty roles are **per branch** (branches may run different setups).
- A service carries **default slots**; generated events copy them. Editing an event's slots afterwards does not touch the service, and vice versa.
- Events get `ends_at`. Generated events default to start + 2 hours (editable). Needed for the overlap rule.
- v1 assigns only members of the event's own branch. Cross-branch serving waits for v2 (ties into the swap rule).
- A member can respond only while status is `assigned` and the event has not started. After accepting, changing your mind means asking an admin (v2 adds swaps).
- An admin can remove an assignment that is `assigned` or `accepted`; declined rows are kept (they power rule 2) and are never re-opened.
- No emails or in-app notifications in v1; those are Phase 5.
- Timezone: app timezone `Asia/Manila` (already set in `config/app.php`); "before `starts_at`" is checked in that zone.
- A user has exactly one `branch_id`, so the overlap check (rule 1) runs across all of a member's assignments, not per branch.
- A declined slot reopens: a *different* member can fill it (only the decliner is blocked, rule 2).
- Editing an event's slots: lowering a quantity below the current non-declined fill, or removing a slot that has non-declined assignments, is refused with a message ("remove assignments first").
- Generation skips an occurrence if the event's branch already has an event for that service on that **date** (not exact time), so changing a service's time and regenerating makes no duplicates. Existing events are never changed by service edits.
- Inactive duty roles are hidden from new slot pickers; existing slots and assignments using them keep working.
- Events: future events can be deleted (assignments go with them, confirm modal); past events cannot be deleted, to keep history.
- ~~Branch-admins see default slots read-only~~ superseded by the owner rule below: `ServicePolicy` create/update/delete open to branch-admin for own branch.

## Screens / routes
**Filament (`/admin`)**
- Duty Roles resource: name, sort order, active. Branch-scoped.
- Services resource (existing): add a "Default slots" repeater (duty role + quantity).
- Events resource: list (filters: branch, upcoming/past), form (branch, optional service, name, starts_at, ends_at, notes, slots repeater), and an **Assignments** relation manager on the event page showing each slot as "filled of needed" with assign/remove actions.
- "Generate events" header action on Events: pick branch (super-admin only; others use their own), date range (max 3 months); creates one event per active service occurrence in range, copies default slots, skips occurrences that already exist.

**Member side**
- `/dashboard`: replace the Duties "Coming soon" box with "My upcoming duties" (event name, date/time, branch, role, status) with Accept / Decline buttons when status is `assigned`. Past duties hidden (no history page in v1).
- `POST /duties/{assignment}/accept`, `POST /duties/{assignment}/decline` (named routes `duties.accept`, `duties.decline`), via a Form Request + `DutyAssignmentPolicy`.

## Data changes
- `duty_roles`: id, branch_id (FK, cascade), name, sort_order, is_active, timestamps. Unique (branch_id, name).
- `service_duty_slots`: id, service_id (FK, cascade), duty_role_id (FK, restrict), quantity (tinyint >= 1). Unique (service_id, duty_role_id).
- `events`: id, branch_id (FK, restrict), service_id (nullable FK, null on delete), name, starts_at, ends_at, notes (nullable), timestamps. Unique (service_id, starts_at) to stop duplicate generation.
- `event_duty_slots`: id, event_id (FK, cascade), duty_role_id (FK, restrict), quantity. Unique (event_id, duty_role_id).
- `duty_assignments`: id, branch_id (FK, copied from event, for scoping), event_id (FK, cascade), duty_role_id (FK, restrict), user_id (FK, **restrict**: settles Backlog item "cascade/restrict for duty_assignments -> users"; protects history), assigned_by (nullable FK users, null on delete), status (`assigned` | `accepted` | `declined`; v2 adds `swap_requested` | `swapped`), responded_at (nullable), timestamps.
- All models define `$fillable`; branch-scoped ones use the `BelongsToBranch` trait / `visibleTo()`.

## Rules (Pest tests written BEFORE the enforcing code, in a `DutyRosterService` / Actions)
1. **No double-booking:** a member cannot hold a non-declined assignment on two events whose times overlap (includes the same event twice).
2. **No re-assigning a declined duty:** if a member declined role R on event E, they cannot be assigned R on E again.
3. **No over-filling:** non-declined assignments for (event, role) cannot exceed the slot quantity; assigning a role the event has no slot for is refused.
4. **Same branch only (v1):** the member's `branch_id` must equal the event's.
5. **Respond rules:** only the assigned member, only from `assigned`, only before `starts_at`.
6. Rules live in the service class, not only in Filament forms, so every entry point gets them.

## Permissions
- **Owner rule (2026-11-25):** all admins can do what the super-admin can, but only for their own branch; only the super-admin works across all branches.
- super-admin: everything, all branches.
- branch-head and branch-admin: identical roster powers for own branch: duty roles, service default slots (and services), events, assignments. Role assignment and creating branches stay super-admin only (CLAUDE.md rule; unchanged).
- member: sees and responds to own assignments only. No `/admin`.
- Tests: cross-branch access denied (Filament queries + policies), member cannot accept someone else's duty, member cannot respond after start or twice, branch-admin CAN manage own-branch duty roles/services but not another branch's.

## Seed data (`migrate:fresh --seed`)
- Per branch: 3 placeholder roles (`Duty role A/B/C`, TODO: real names from owner), default slots on each seeded service (TODO: real counts).
- Events generated for the next 4 weeks; demo members assigned with a mix of assigned/accepted/declined, some slots left empty.

## Out of scope (v1)
- Swaps (request, accept by another member, admin approval, super-admin for cross-branch) -> v2.
- Availability (members marking dates they can't serve) -> v2.
- Cross-branch assignments -> v2.
- Notifications and reminders -> Phase 5.
- Announcements (dashboard box stays "Coming soon") -> Phase 5.
- Duty history page, calendar view, auto-fill / rotation suggestions -> Backlog.
