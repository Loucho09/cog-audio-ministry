<?php

use App\Enums\DutyStatus;
use App\Exceptions\RosterRuleViolation;
use App\Models\Branch;
use App\Models\DutyAssignment;
use App\Models\DutyRole;
use App\Models\Event;
use App\Models\User;
use App\Services\DutyRosterService;
use Illuminate\Support\Carbon;

function rosterEvent(string $start = '09:00', string $end = '11:00', int $quantity = 1, ?DutyRole $role = null, ?Event $like = null): array
{
    $day = Carbon::now()->addWeek()->startOfDay();
    $event = $like
        ? Event::factory()->create([
            'branch_id' => $like->branch_id,
            'starts_at' => $day->copy()->setTimeFromTimeString($start),
            'ends_at' => $day->copy()->setTimeFromTimeString($end),
        ])
        : Event::factory()->create([
            'starts_at' => $day->copy()->setTimeFromTimeString($start),
            'ends_at' => $day->copy()->setTimeFromTimeString($end),
        ]);
    $role ??= DutyRole::factory()->create(['branch_id' => $event->branch_id]);
    $event->dutySlots()->create(['duty_role_id' => $role->id, 'quantity' => $quantity]);

    return [$event, $role];
}

function rosterMember(Event $event): User
{
    return User::factory()->member($event->branch)->create();
}

function svc(): DutyRosterService
{
    return app(DutyRosterService::class);
}

test('assign creates an assigned duty in the event branch', function () {
    [$event, $role] = rosterEvent();
    $member = rosterMember($event);
    $by = User::factory()->branchHead($event->branch)->create();

    $a = svc()->assign($event, $role, $member, $by);

    expect($a->status)->toBe(DutyStatus::Assigned)
        ->and($a->branch_id)->toBe($event->branch_id)
        ->and($a->assigned_by)->toBe($by->id);
});

test('cannot assign the same member twice to one event', function () {
    [$event, $role] = rosterEvent(quantity: 2);
    $member = rosterMember($event);
    svc()->assign($event, $role, $member);

    expect(fn () => svc()->assign($event, $role, $member))->toThrow(RosterRuleViolation::class, 'already serving');
});

test('cannot assign a member to overlapping events', function () {
    [$e1, $r1] = rosterEvent('09:00', '11:00');
    [$e2, $r2] = rosterEvent('10:00', '12:00', like: $e1);
    $member = rosterMember($e1);
    svc()->assign($e1, $r1, $member);

    expect(fn () => svc()->assign($e2, $r2, $member))->toThrow(RosterRuleViolation::class, 'already serving');
});

test('back-to-back events are allowed', function () {
    [$e1, $r1] = rosterEvent('09:00', '11:00');
    [$e2, $r2] = rosterEvent('11:00', '13:00', like: $e1);
    $member = rosterMember($e1);
    svc()->assign($e1, $r1, $member);

    expect(svc()->assign($e2, $r2, $member))->toBeInstanceOf(DutyAssignment::class);
});

test('declined assignment on another event does not block overlap', function () {
    [$e1, $r1] = rosterEvent('09:00', '11:00');
    [$e2, $r2] = rosterEvent('10:00', '12:00', like: $e1);
    $member = rosterMember($e1);
    DutyAssignment::factory()->create([
        'event_id' => $e1->id, 'duty_role_id' => $r1->id, 'user_id' => $member->id, 'status' => DutyStatus::Declined,
    ]);

    expect(svc()->assign($e2, $r2, $member))->toBeInstanceOf(DutyAssignment::class);
});

test('cannot re-assign a duty the member declined', function () {
    [$event, $role] = rosterEvent();
    $member = rosterMember($event);
    DutyAssignment::factory()->create([
        'event_id' => $event->id, 'duty_role_id' => $role->id, 'user_id' => $member->id, 'status' => DutyStatus::Declined,
    ]);

    expect(fn () => svc()->assign($event, $role, $member))->toThrow(RosterRuleViolation::class, 'declined this duty');
});

test('a different member can fill a declined slot', function () {
    [$event, $role] = rosterEvent();
    $first = rosterMember($event);
    DutyAssignment::factory()->create([
        'event_id' => $event->id, 'duty_role_id' => $role->id, 'user_id' => $first->id, 'status' => DutyStatus::Declined,
    ]);

    expect(svc()->assign($event, $role, rosterMember($event)))->toBeInstanceOf(DutyAssignment::class);
});

test('cannot over-fill a slot', function () {
    [$event, $role] = rosterEvent(quantity: 1);
    svc()->assign($event, $role, rosterMember($event));

    expect(fn () => svc()->assign($event, $role, rosterMember($event)))->toThrow(RosterRuleViolation::class, 'already full');
});

test('cannot assign a role the event has no slot for', function () {
    [$event] = rosterEvent();
    $other = DutyRole::factory()->create(['branch_id' => $event->branch_id]);

    expect(fn () => svc()->assign($event, $other, rosterMember($event)))->toThrow(RosterRuleViolation::class, 'no slot');
});

test('cannot assign a member from another branch', function () {
    [$event, $role] = rosterEvent();
    $outsider = User::factory()->member(Branch::factory()->create())->create();

    expect(fn () => svc()->assign($event, $role, $outsider))->toThrow(RosterRuleViolation::class, 'different branch');
});

test('rule 4 uses the members current branch', function () {
    [$event, $role] = rosterEvent();
    $member = rosterMember($event);
    $member->forceFill(['branch_id' => Branch::factory()->create()->id])->save();

    expect(fn () => svc()->assign($event, $role, $member))->toThrow(RosterRuleViolation::class, 'different branch');
});

test('cannot assign to an event that has started', function () {
    [$event, $role] = rosterEvent();
    $member = rosterMember($event);
    $this->travelTo($event->starts_at->copy()->addMinutes(5));

    expect(fn () => svc()->assign($event, $role, $member))->toThrow(RosterRuleViolation::class, 'already started');
});

test('remove deletes an accepted assignment and frees the slot', function () {
    [$event, $role] = rosterEvent(quantity: 1);
    $a = svc()->assign($event, $role, rosterMember($event));
    $a->update(['status' => DutyStatus::Accepted]);

    svc()->remove($a);

    expect(DutyAssignment::count())->toBe(0)
        ->and(svc()->assign($event, $role, rosterMember($event)))->toBeInstanceOf(DutyAssignment::class);
});

test('remove refuses a declined assignment', function () {
    [$event, $role] = rosterEvent();
    $a = DutyAssignment::factory()->create([
        'event_id' => $event->id, 'duty_role_id' => $role->id, 'status' => DutyStatus::Declined,
    ]);

    expect(fn () => svc()->remove($a))->toThrow(RosterRuleViolation::class, 'declined duty');
});

test('remove refuses a duty on an event that has started', function () {
    [$event, $role] = rosterEvent();
    $a = svc()->assign($event, $role, rosterMember($event));
    $this->travelTo($event->starts_at->copy()->addMinutes(5));

    expect(fn () => svc()->remove($a))->toThrow(RosterRuleViolation::class, 'already started');
});
