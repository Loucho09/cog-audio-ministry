# Duty Roster v1 Implementation Plan
Status: built 2026-11-25 on feature/duty-roster

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Admins build a per-branch duty roster (events with fixed duty slots, filled with members) in Filament; members accept or decline their duties on `/dashboard`.

**Architecture:** Five new tables (`duty_roles`, `service_duty_slots`, `events`, `event_duty_slots`, `duty_assignments`). All roster rules live in `App\Services\DutyRosterService` and `App\Services\EventGenerator`, throwing `App\Exceptions\RosterRuleViolation`; Filament and the member controller only call those services. Branch scoping reuses the `BelongsToBranch` trait (`visibleTo()`) and auto-discovered Policies; `Gate::before` already lets super-admin pass.

**Tech Stack:** Laravel, Filament v5.12 (`Filament\Schemas\Schema`), Spatie Permission, Pest, Blade + Tailwind.

**Spec:** [[duty-roster]] (`brain/Features/duty-roster.md`). Executors read both.

## Global Constraints
- Branch: `feature/duty-roster`. Run `php artisan test` and `./vendor/bin/pint` before every commit.
- Rule tests are written and seen failing BEFORE the enforcing code (Tasks 4-6).
- Every model defines `$fillable`. Every branch-scoped model (`DutyRole`, `Event`, `DutyAssignment`) has `branch_id` and uses `BelongsToBranch`.
- Owner rule: branch-head and branch-admin have identical powers, own branch only; only super-admin crosses branches. Role assignment and branch creation stay super-admin only.
- No new packages. No notifications (Phase 5). No hardcoded hex colours; new UI works in light and dark mode at 360px.
- Placeholder duty role names `Duty role A`, `Duty role B`, `Duty role C` with a `// TODO: real roles from owner` comment. Never invent real names.
- Times are `Asia/Manila` (app timezone). Overlap = `a.starts_at < b.ends_at AND b.starts_at < a.ends_at` (back-to-back is fine).
- "Active" assignment = status `assigned` or `accepted` (i.e. not `declined`).

## Review Focus
1. Member double-clicks Accept / submits a stale form after already declining: second request must get 403/422, not change status (Task 11 test).
2. Admin assigns a member whose branch was changed after the event was created: rule 4 compares the member's *current* `branch_id` (Task 4 test).
3. Event edited to a new time that now overlaps another of a member's active duties: the edit is refused (Task 5 test), not silently creating a double-booking.
4. Generate events across a range with a service whose `starts_on`/`ends_on` window cuts the range: only in-window dates created (Task 6 test).
5. Branch-admin opens another branch's event URL in `/admin` directly: 404/403 (Task 7 policy test + Task 9 resource test).

---

### Task 1: Branch-admin can manage services

**Files:**
- Modify: `app/Policies/ServicePolicy.php` (`create`)
- Test: `tests/Feature/Admin/ServiceResourceTest.php`

**Interfaces:** Produces `ServicePolicy::create()` true for branch-head and branch-admin with a branch.

- [x] Step 1: Add tests `branch-admin can create and update services in own branch` and `branch-admin cannot update another branch's service` (`$user->can('update', $service)` true / false; `User::factory()->branchAdmin($branch)`).
- [x] Step 2: Run `php artisan test --filter=ServiceResourceTest`. Expected: the new "can" test FAILS.
- [x] Step 3: `create()` uses `hasAnyRole(['branch-head', 'branch-admin'])`. Fix any existing test that asserted the old denial (it encoded the old rule).
- [x] Step 4: Run the filter again. Expected: PASS.
- [x] Step 5: Commit `feat(roles): branch-admins manage their branch's services`.

### Task 2: Duty roles and service default slots (schema + models)

**Files:**
- Create: `database/migrations/2026_11_26_100000_create_duty_roles_table.php`, `database/migrations/2026_11_26_100100_create_service_duty_slots_table.php`, `app/Models/DutyRole.php`, `app/Models/ServiceDutySlot.php`, `database/factories/DutyRoleFactory.php`
- Modify: `app/Models/Service.php` (add relation)
- Test: `tests/Feature/Roster/DutyRoleModelTest.php`

**Interfaces:**
- Produces: `DutyRole` (fillable `branch_id, name, sort_order, is_active`; casts bool/int; `branch()`, `scopeActive()`, `scopeOrdered()`), `ServiceDutySlot` (fillable `service_id, duty_role_id, quantity`; `service()`, `dutyRole()`), `Service::dutySlots(): HasMany`. `DutyRoleFactory` default makes a branch via `Branch::factory()`.
- Columns exactly as spec "Data changes": unique `(branch_id, name)` on roles; unique `(service_id, duty_role_id)` on slots; `quantity` unsignedTinyInteger; role FK on slots `restrictOnDelete`.

- [x] Step 1: Tests: `duty role names are unique per branch` (same name in two branches OK, same branch throws `QueryException`); `service has default duty slots`; `visibleTo scopes duty roles to the user's branch`.
- [x] Step 2: Run `php artisan test --filter=DutyRoleModelTest`. Expected: FAIL (class not found).
- [x] Step 3: Migrations, models, factory, `Service::dutySlots()`.
- [x] Step 4: Run filter. Expected: PASS.
- [x] Step 5: Commit `feat(roster): duty roles and service default slots`.

### Task 3: Events, event slots, assignments (schema + models)

**Files:**
- Create: `database/migrations/2026_11_26_100200_create_events_table.php`, `..._100300_create_event_duty_slots_table.php`, `..._100400_create_duty_assignments_table.php`, `app/Enums/DutyStatus.php`, `app/Models/Event.php`, `app/Models/EventDutySlot.php`, `app/Models/DutyAssignment.php`, `database/factories/EventFactory.php`, `database/factories/DutyAssignmentFactory.php`
- Test: `tests/Feature/Roster/EventModelTest.php`

**Interfaces:**
- Produces: `enum DutyStatus: string { Assigned = 'assigned'; Accepted = 'accepted'; Declined = 'declined'; }` with `label(): string`.
- `Event` (fillable `branch_id, service_id, name, starts_at, ends_at, notes`; datetime casts; `branch()`, `service()`, `dutySlots(): HasMany`, `assignments(): HasMany`, `scopeUpcoming()` = `ends_at >= now()`; `hasStarted(): bool`).
- `EventDutySlot` (fillable `event_id, duty_role_id, quantity`; `event()`, `dutyRole()`).
- `DutyAssignment` (fillable `branch_id, event_id, duty_role_id, user_id, assigned_by, status, responded_at`; `status` cast to `DutyStatus`; `event()`, `dutyRole()`, `user()`, `assigner()`; `scopeActive()` = status != declined).
- `User::dutyAssignments(): HasMany` (modify `app/Models/User.php`).
- `EventFactory` default: branch factory, `starts_at` = next week 09:00, `ends_at` = +2h. `DutyAssignmentFactory` default status `assigned`, takes `branch_id` from its event.
- FKs per spec: `events.branch_id` restrict, `events.service_id` nullOnDelete, unique `(service_id, starts_at)`; assignments: event cascade, role restrict, **user restrict**, `assigned_by` nullOnDelete.

- [x] Step 1: Tests: `deleting a user with assignments is blocked` (expects `QueryException`); `deleting an event deletes its slots and assignments`; `active scope excludes declined`.
- [x] Step 2: Run `--filter=EventModelTest`. Expected: FAIL.
- [x] Step 3: Implement.
- [x] Step 4: Run. Expected: PASS. Also `php artisan migrate:fresh --seed` still succeeds.
- [x] Step 5: Commit `feat(roster): events, event slots and duty assignments`.

### Task 4: Assignment rules (TDD) in DutyRosterService

**Files:**
- Create: `app/Exceptions/RosterRuleViolation.php`, `app/Services/DutyRosterService.php`
- Test: `tests/Feature/Roster/AssignRulesTest.php`

**Interfaces:**
- Produces: `class RosterRuleViolation extends \DomainException` (message is user-facing, plain English).
- `DutyRosterService::assign(Event $event, DutyRole $role, User $member, ?User $by = null): DutyAssignment` (creates with status `assigned`, `branch_id = $event->branch_id`, in a DB transaction with `lockForUpdate` on the event's slot row).
- `DutyRosterService::remove(DutyAssignment $assignment): void` (deletes if `assigned`/`accepted`; throws for `declined`).

- [x] Step 1: Write failing tests, each `expect(fn () => ...)->toThrow(RosterRuleViolation::class)`:
  - `cannot assign the same member twice to one event` (rule 1)
  - `cannot assign a member to overlapping events` (event 09:00-11:00 vs 10:00-12:00); `back-to-back events are allowed` (11:00 start) succeeds
  - `declined assignment on another event does not block overlap` (declined rows ignored)
  - `cannot re-assign a duty the member declined` (rule 2); `a different member can fill a declined slot` succeeds
  - `cannot over-fill a slot` (quantity 1, second active member throws); `cannot assign a role the event has no slot for`
  - `cannot assign a member from another branch` (rule 4, using member's current `branch_id`)
  - `cannot assign to an event that has started`
  - `remove deletes an accepted assignment and frees the slot`; `remove refuses a declined assignment`
- [x] Step 2: Run `--filter=AssignRulesTest`. Expected: FAIL (class not found).
- [x] Step 3: Implement `assign()` / `remove()`; checks in order: started, branch, slot exists, declined-before, overlap, capacity.
- [x] Step 4: Run. Expected: PASS.
- [x] Step 5: Commit `feat(roster): assignment rules in DutyRosterService`.

### Task 5: Respond rules + event change guards (TDD)

**Files:**
- Modify: `app/Services/DutyRosterService.php`
- Test: `tests/Feature/Roster/RespondRulesTest.php`, `tests/Feature/Roster/EventChangeGuardTest.php`

**Interfaces:**
- Produces: `respond(DutyAssignment $a, User $member, DutyStatus $answer): DutyAssignment` (answer must be Accepted or Declined; sets `responded_at`).
- `assertSlotChangesAllowed(Event $event, array $quantitiesByRoleId): void` (`[roleId => qty]`; a missing role id = slot removed).
- `assertTimeChangeAllowed(Event $event, CarbonInterface $startsAt, CarbonInterface $endsAt): void` (no active member of this event would overlap another of their active duties; `ends_at` must be after `starts_at`).

- [x] Step 1: Failing tests: `member can accept own assigned duty`; `member can decline own assigned duty`; `cannot respond to someone else's duty`; `cannot respond twice` (accepted then declined throws); `cannot respond after the event started`; `cannot lower a slot below its active fill`; `cannot remove a slot with active assignments`; `can lower a slot when only declined rows remain`; `cannot move an event onto a member's other duty time`.
- [x] Step 2: Run both filters. Expected: FAIL.
- [x] Step 3: Implement.
- [x] Step 4: Run. Expected: PASS.
- [x] Step 5: Commit `feat(roster): respond rules and event change guards`.

### Task 6: EventGenerator (TDD)

**Files:**
- Create: `app/Services/EventGenerator.php`
- Test: `tests/Feature/Roster/EventGeneratorTest.php`

**Interfaces:**
- Produces: `EventGenerator::generate(Branch $branch, CarbonImmutable $from, CarbonImmutable $to): int` (count created). Throws `RosterRuleViolation` if `$to` < `$from` or range > 3 months.
- Behaviour: for each active service of the branch, each date in range matching `day_of_week` and inside `starts_on`/`ends_on`: skip if an event with that `service_id` exists on that **date**; else create event (`name` = service name, `starts_at` = date + `start_time`, `ends_at` = +2h) and copy `dutySlots` into `event_duty_slots`.

- [x] Step 1: Failing tests: `generates one event per weekly occurrence with copied slots`; `running twice creates no duplicates` (second call returns 0); `changed service time does not duplicate a date`; `respects the service date window`; `ignores inactive services and other branches`; `rejects ranges over 3 months`.
- [x] Step 2: Run. Expected: FAIL.
- [x] Step 3: Implement.
- [x] Step 4: Run. Expected: PASS.
- [x] Step 5: Commit `feat(roster): generate events from the service schedule`.

### Task 7: Policies (branch scoping)

**Files:**
- Create: `app/Policies/DutyRolePolicy.php`, `app/Policies/EventPolicy.php`, `app/Policies/DutyAssignmentPolicy.php`
- Test: `tests/Feature/Roles/RosterPoliciesTest.php`

**Interfaces:**
- Admin check (all three): `$user->branch_id !== null && $user->hasAnyRole(['branch-head', 'branch-admin'])`; per-record adds `belongsToBranch($user->branch_id)`. Same shape as `ServicePolicy`.
- `EventPolicy::delete()` also requires `! $event->hasStarted()` (past events kept). Super-admin bypasses via `Gate::before`, so also enforce the past-event rule in the Filament delete action (Task 9).
- `DutyAssignmentPolicy::respond(User $user, DutyAssignment $a): bool` = `$a->user_id === $user->id`.

- [x] Step 1: Failing tests for each policy: own-branch head and admin allowed; other-branch admin denied; member denied `viewAny`; `respond` true only for the owner; `delete` false for a past event.
- [x] Step 2: Run `--filter=RosterPoliciesTest`. Expected: FAIL.
- [x] Step 3: Implement.
- [x] Step 4: Run. Expected: PASS.
- [x] Step 5: Commit `feat(roster): branch-scoped roster policies`.

### Task 8: Filament: Duty Roles resource + service default slots

**Files:**
- Create: `app/Filament/Resources/DutyRoles/DutyRoleResource.php` + `Pages/ListDutyRoles.php`, `Pages/CreateDutyRole.php`, `Pages/EditDutyRole.php` (copy the Services resource layout)
- Modify: `app/Filament/Resources/Services/ServiceResource.php` (add `Repeater::make('dutySlots')->relationship()` with `duty_role_id` Select of active roles of the service's branch, `quantity` 1-20)
- Test: `tests/Feature/Admin/DutyRoleResourceTest.php`

**Interfaces:** `getEloquentQuery()` uses `->visibleTo(auth()->user())`; `branch_id` Select visible only to super-admin, else filled from `auth()->user()->branch_id` (same as ServiceResource). Navigation group "Roster".

- [x] Step 1: Failing tests: list shows only own-branch roles; branch-admin can create a role; other branch's edit page returns 403/404; service form saves default slots.
- [x] Step 2: Run. Expected: FAIL.
- [x] Step 3: Implement.
- [x] Step 4: Run. Expected: PASS.
- [x] Step 5: Commit `feat(admin): duty roles and service default slots`.

### Task 9: Filament: Events resource + Generate action

**Files:**
- Create: `app/Filament/Resources/Events/EventResource.php`, `Pages/ListEvents.php` (header actions: Create, **Generate events**), `Pages/CreateEvent.php`, `Pages/EditEvent.php`
- Test: `tests/Feature/Admin/EventResourceTest.php`

**Interfaces:**
- Form: branch (super-admin only), service (optional, own branch), name, `starts_at`, `ends_at` (after `starts_at`), notes, `dutySlots` repeater (as Task 8).
- `EditEvent::beforeSave()`: read the repeater + time state, call `assertSlotChangesAllowed()` and `assertTimeChangeAllowed()`; on `RosterRuleViolation` send a danger `Notification` with the message and `throw new Halt()`.
- Table: name, branch, starts_at, "filled / needed" count; filters: branch (super-admin), Upcoming (default) / Past. Delete action hidden when `hasStarted()`.
- Generate action form: branch (super-admin only), from, to; calls `EventGenerator::generate()`; success notification "N events created"; violation shown as danger notification.

- [x] Step 1: Failing tests: branch-admin sees only own events; generate action creates events; lowering a filled slot shows the error and does not save; past event has no delete action.
- [x] Step 2: Run. Expected: FAIL.
- [x] Step 3: Implement.
- [x] Step 4: Run. Expected: PASS.
- [x] Step 5: Commit `feat(admin): events resource with generate action`.

### Task 10: Filament: Assignments relation manager

**Files:**
- Create: `app/Filament/Resources/Events/RelationManagers/AssignmentsRelationManager.php`
- Modify: `app/Filament/Resources/Events/EventResource.php` (`getRelations()`)
- Test: `tests/Feature/Admin/EventAssignmentsTest.php`

**Interfaces:** Table rows = assignments (member, role, status badge, responded_at). Heading/description lists each slot "Role: filled of needed". "Assign" header action: Select role (event's slots), Select member (active members of event's branch), calls `DutyRosterService::assign($event, $role, $member, auth()->user())`. Row "Remove" action calls `remove()`, hidden for declined. Violations → danger notification, no save.

- [x] Step 1: Failing tests: assign happy path; assign over capacity shows error and creates nothing; remove frees slot.
- [x] Step 2: Run. Expected: FAIL.
- [x] Step 3: Implement.
- [x] Step 4: Run. Expected: PASS.
- [x] Step 5: Commit `feat(admin): assign members to event duty slots`.

### Task 11: Member side: dashboard duties + accept/decline

**Files:**
- Create: `app/Http/Controllers/DutyResponseController.php`
- Modify: `routes/web.php` (in an `['auth', 'verified']` group: `POST /duties/{assignment}/accept` → `duties.accept`, `POST /duties/{assignment}/decline` → `duties.decline`, both `throttle:30,1`), `app/Http/Controllers/DashboardController.php` (pass `$duties`), `resources/views/dashboard.blade.php` (replace lines 101-105)
- Test: `tests/Feature/Roster/MemberDutiesTest.php`

**Interfaces:**
- Controller methods `accept(DutyAssignment $assignment)` / `decline(...)`: `$this->authorize('respond', $assignment)`, call `DutyRosterService::respond()`, redirect to `dashboard` with flash `status`; `RosterRuleViolation` → back with error, HTTP 302, status unchanged.
- `$duties` = own upcoming active assignments + own upcoming declined (shown as "Declined"), eager-loaded `event.branch`, `dutyRole`, ordered by `events.starts_at`.
- View: "My upcoming duties" list: event name, date/time (`D, M j · g:i A`), branch, role, status; Accept/Decline buttons (CSRF forms) only when `assigned`; empty state "No duties yet. Your branch leaders will assign you." Use existing theme tokens and the button component; Announcements box stays "Coming soon".

- [x] Step 1: Failing tests: dashboard lists own duty and not others'; accept changes status; decline changes status; accepting someone else's duty is 403; second submit after decline does not change status; past duties hidden.
- [x] Step 2: Run. Expected: FAIL.
- [x] Step 3: Implement.
- [x] Step 4: Run. Expected: PASS. Check `/dashboard` at 360px in light and dark mode.
- [x] Step 5: Commit `feat(dashboard): members see and answer their duties`.

### Task 12: Demo seed data

**Files:**
- Create: `database/seeders/DutyRosterSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php` (add after `ServiceSeeder`, local/testing only like DemoUserSeeder)
- Test: `tests/Feature/Roster/RosterSeederTest.php`

**Interfaces:** Per branch: roles `Duty role A/B/C` (TODO comment), each seeded service gets slots A×1, B×1, C×2 (TODO real counts); `EventGenerator::generate()` for the next 4 weeks; assign demo members through `DutyRosterService::assign()` (so rules hold), then respond some as accepted/declined; leave some slots empty.

- [x] Step 1: Failing test: after `$this->seed(DatabaseSeeder::class)` there are events, at least one of each status, and no slot over capacity.
- [x] Step 2: Run. Expected: FAIL.
- [x] Step 3: Implement.
- [x] Step 4: Run, then `php artisan migrate:fresh --seed`. Expected: PASS, seed succeeds.
- [x] Step 5: Commit `feat(seed): demo duty roster`.

### Task 13: Review and record

- [x] Step 1: Run full `php artisan test` and `./vendor/bin/pint --test`. Expected: all green.
- [x] Step 2: `reviewer` agent on the whole branch; fix findings.
- [x] Step 3: `brain-keeper`: spec status "done (v1)", Roadmap Phase 4 v1 done / v2 next, decision note for the owner admin rule, Backlog item "cascade/restrict duty_assignments -> users" settled (restrict), Backlog items: duty history page, calendar view, auto-fill, real duty role names and counts (TODO from owner).
- [x] Step 4: Commit, then merge `feature/duty-roster` into `main` when the owner OKs.
