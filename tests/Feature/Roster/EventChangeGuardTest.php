<?php

use App\Enums\DutyStatus;
use App\Exceptions\RosterRuleViolation;
use App\Models\DutyAssignment;
use App\Models\DutyRole;

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

    expect(fn () => svc()->assertSlotChangesAllowed($event, [$role->id => 1]))->not->toThrow(RosterRuleViolation::class);
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

test('updateEvent saves times and syncs slots', function () {
    [$event, $role] = rosterEvent(quantity: 1);
    $newRole = DutyRole::factory()->create(['branch_id' => $event->branch_id]);
    $start = $event->starts_at->copy()->setTime(8, 0);
    $end = $event->ends_at->copy()->setTime(10, 0);

    $updated = svc()->updateEvent($event, ['name' => 'Renamed', 'starts_at' => $start, 'ends_at' => $end], [$newRole->id => 3]);

    expect($updated->name)->toBe('Renamed')
        ->and($updated->starts_at->equalTo($start))->toBeTrue()
        ->and($updated->dutySlots()->pluck('quantity', 'duty_role_id')->all())->toBe([$newRole->id => 3]);
});

test('updateEvent refuses lowering a filled slot and changes nothing', function () {
    [$event, $role] = rosterEvent(quantity: 2);
    svc()->assign($event, $role, rosterMember($event));
    svc()->assign($event, $role, rosterMember($event));
    $name = $event->name;

    expect(fn () => svc()->updateEvent($event, ['name' => 'Changed'], [$role->id => 1]))
        ->toThrow(RosterRuleViolation::class, 'already filled');

    expect($event->fresh()->name)->toBe($name)
        ->and($event->dutySlots()->first()->quantity)->toBe(2);
});

test('updateEvent refuses a clashing time and changes nothing', function () {
    [$e1, $r1] = rosterEvent('09:00', '11:00');
    [$e2, $r2] = rosterEvent('12:00', '14:00', like: $e1);
    $member = rosterMember($e1);
    svc()->assign($e1, $r1, $member);
    svc()->assign($e2, $r2, $member);
    $start = $e2->starts_at;

    expect(fn () => svc()->updateEvent($e2, ['starts_at' => $start->copy()->setTime(10, 0), 'name' => 'Moved'], [$r2->id => 1]))
        ->toThrow(RosterRuleViolation::class, 'already serving');

    expect($e2->fresh()->starts_at->equalTo($start))->toBeTrue()
        ->and($e2->fresh()->name)->not->toBe('Moved');
});
