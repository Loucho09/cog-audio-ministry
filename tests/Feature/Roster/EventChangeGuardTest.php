<?php

use App\Enums\DutyStatus;
use App\Exceptions\RosterRuleViolation;
use App\Models\DutyAssignment;

test('cannot lower a slot below its active fill', function () {
    [$event, $role] = rosterEvent(quantity: 2);
    svc()->assign($event, $role, rosterMember($event));
    svc()->assign($event, $role, rosterMember($event));

    expect(fn () => svc()->assertSlotChangesAllowed($event, [$role->id => 1]))
        ->toThrow(RosterRuleViolation::class, 'already filled');
});

test('cannot remove a slot with active assignments', function () {
    [$event, $role] = rosterEvent();
    svc()->assign($event, $role, rosterMember($event));

    expect(fn () => svc()->assertSlotChangesAllowed($event, []))
        ->toThrow(RosterRuleViolation::class, 'already filled');
});

test('can lower a slot when only declined rows remain', function () {
    [$event, $role] = rosterEvent(quantity: 2);
    DutyAssignment::factory()->create([
        'event_id' => $event->id, 'duty_role_id' => $role->id, 'status' => DutyStatus::Declined,
    ]);
    svc()->assign($event, $role, rosterMember($event));

    svc()->assertSlotChangesAllowed($event, [$role->id => 1]);
    expect(true)->toBeTrue();
});

test('cannot move an event onto a member\'s other duty time', function () {
    [$e1, $r1] = rosterEvent('09:00', '11:00');
    [$e2, $r2] = rosterEvent('12:00', '14:00', like: $e1);
    $member = rosterMember($e1);
    svc()->assign($e1, $r1, $member);
    svc()->assign($e2, $r2, $member);

    expect(fn () => svc()->assertTimeChangeAllowed($e2, $e2->starts_at->copy()->setTime(10, 0), $e2->ends_at->copy()->setTime(12, 30)))
        ->toThrow(RosterRuleViolation::class, 'already serving');
});

test('time change allowed when no clash and end must be after start', function () {
    [$e1, $r1] = rosterEvent('09:00', '11:00');
    svc()->assign($e1, $r1, rosterMember($e1));

    svc()->assertTimeChangeAllowed($e1, $e1->starts_at->copy()->setTime(9, 30), $e1->ends_at->copy()->setTime(11, 30));

    expect(fn () => svc()->assertTimeChangeAllowed($e1, $e1->ends_at, $e1->starts_at))
        ->toThrow(RosterRuleViolation::class, 'after');
});
