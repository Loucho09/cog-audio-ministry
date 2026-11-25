<?php

use App\Enums\DutyStatus;
use App\Exceptions\RosterRuleViolation;
use App\Models\DutyAssignment;
use App\Models\User;

test('guest is created auto-confirmed in the event branch', function () {
    [$event, $role] = rosterEvent();
    $by = User::factory()->branchAdmin($event->branch)->create();

    $a = svc()->assignGuest($event, $role, '  Juan   dela  Cruz ', $by);

    expect($a->user_id)->toBeNull()
        ->and($a->guest_name)->toBe('Juan dela Cruz')
        ->and($a->status)->toBe(DutyStatus::Accepted)
        ->and($a->responded_at)->not->toBeNull()
        ->and($a->branch_id)->toBe($event->branch_id)
        ->and($a->assigned_by)->toBe($by->id)
        ->and($a->isGuest())->toBeTrue();
});

test('guest fills capacity and a full slot blocks another guest', function () {
    [$event, $role] = rosterEvent(quantity: 1);
    svc()->assignGuest($event, $role, 'Guest One');

    expect(fn () => svc()->assignGuest($event, $role, 'Guest Two'))
        ->toThrow(RosterRuleViolation::class, 'already full');
});

test('a slot filled by a guest blocks a member', function () {
    [$event, $role] = rosterEvent(quantity: 1);
    svc()->assignGuest($event, $role, 'Guest One');

    expect(fn () => svc()->assign($event, $role, rosterMember($event)))
        ->toThrow(RosterRuleViolation::class, 'already full');
});

test('a member filled slot blocks a guest', function () {
    [$event, $role] = rosterEvent(quantity: 1);
    svc()->assign($event, $role, rosterMember($event));

    expect(fn () => svc()->assignGuest($event, $role, 'Guest One'))
        ->toThrow(RosterRuleViolation::class, 'already full');
});

test('guest cannot be assigned after the event started', function () {
    [$event, $role] = rosterEvent();
    $event->update(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);

    expect(fn () => svc()->assignGuest($event, $role, 'Guest One'))
        ->toThrow(RosterRuleViolation::class, 'already started');
});

test('guest cannot be assigned to a duty the event has no slot for', function () {
    [$event] = rosterEvent();
    [, $otherRole] = rosterEvent();

    expect(fn () => svc()->assignGuest($event, $otherRole, 'Guest One'))
        ->toThrow(RosterRuleViolation::class, 'no slot');
});

test('guest name must be 2 to 100 characters', function (string $name) {
    [$event, $role] = rosterEvent();

    expect(fn () => svc()->assignGuest($event, $role, $name))
        ->toThrow(RosterRuleViolation::class, 'name');
})->with(['too short' => 'A', 'too long' => fn () => str_repeat('a', 101), 'blank' => '    ']);

test('same guest name on the same event is blocked case-insensitively', function () {
    [$event, $role] = rosterEvent(quantity: 2);
    svc()->assignGuest($event, $role, 'Maria Santos');

    expect(fn () => svc()->assignGuest($event, $role, '  maria   SANTOS '))
        ->toThrow(RosterRuleViolation::class, 'already serving');
});

test('same guest name on an overlapping event of the same branch is blocked', function () {
    [$e1, $r1] = rosterEvent('09:00', '11:00');
    [$e2, $r2] = rosterEvent('10:00', '12:00', like: $e1);
    svc()->assignGuest($e1, $r1, 'Maria Santos');

    expect(fn () => svc()->assignGuest($e2, $r2, 'maria santos'))
        ->toThrow(RosterRuleViolation::class, 'already serving');
});

test('same guest name in a different branch is allowed', function () {
    [$e1, $r1] = rosterEvent('09:00', '11:00');
    [$e2, $r2] = rosterEvent('09:00', '11:00');
    expect($e2->branch_id)->not->toBe($e1->branch_id);

    svc()->assignGuest($e1, $r1, 'Maria Santos');

    expect(svc()->assignGuest($e2, $r2, 'Maria Santos')->exists)->toBeTrue();
});

test('same guest name at a non-overlapping time is allowed', function () {
    [$e1, $r1] = rosterEvent('09:00', '11:00');
    [$e2, $r2] = rosterEvent('12:00', '14:00', like: $e1);
    svc()->assignGuest($e1, $r1, 'Maria Santos');

    expect(svc()->assignGuest($e2, $r2, 'Maria Santos')->exists)->toBeTrue();
});

test('a declined-style inactive guest row does not block the same name', function () {
    [$event, $role] = rosterEvent(quantity: 2);
    DutyAssignment::factory()->guest()->create([
        'event_id' => $event->id, 'branch_id' => $event->branch_id, 'duty_role_id' => $role->id,
        'guest_name' => 'Maria Santos', 'status' => DutyStatus::Declined,
    ]);

    expect(svc()->assignGuest($event, $role, 'Maria Santos')->exists)->toBeTrue();
});

test('a guest can be removed before the event starts', function () {
    [$event, $role] = rosterEvent();
    $a = svc()->assignGuest($event, $role, 'Guest One');

    svc()->remove($a);

    expect(DutyAssignment::find($a->id))->toBeNull();
});

test('a guest cannot be removed after the event started', function () {
    [$event, $role] = rosterEvent();
    $a = svc()->assignGuest($event, $role, 'Guest One');
    $event->update(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);

    expect(fn () => svc()->remove($a->fresh()))->toThrow(RosterRuleViolation::class, 'already started');
});

test('a guest assignment cannot be answered by a member', function () {
    [$event, $role] = rosterEvent();
    $a = DutyAssignment::factory()->guest()->create([
        'event_id' => $event->id, 'branch_id' => $event->branch_id, 'duty_role_id' => $role->id,
        'status' => DutyStatus::Assigned,
    ]);

    expect(fn () => svc()->respond($a, rosterMember($event), DutyStatus::Accepted))
        ->toThrow(RosterRuleViolation::class, 'not yours');
});

test('updating an event with a guest on it does not crash', function () {
    [$event, $role] = rosterEvent(quantity: 2);
    svc()->assignGuest($event, $role, 'Guest One');

    $updated = svc()->updateEvent($event, [
        'name' => 'Renamed', 'starts_at' => $event->starts_at->copy()->setTime(9, 30), 'ends_at' => $event->ends_at,
    ], [$role->id => 2]);

    expect($updated->name)->toBe('Renamed');
});

test('moving an event onto the same guest name at another event is blocked', function () {
    [$e1, $r1] = rosterEvent('09:00', '11:00');
    [$e2, $r2] = rosterEvent('12:00', '14:00', like: $e1);
    svc()->assignGuest($e1, $r1, 'Maria Santos');
    svc()->assignGuest($e2, $r2, 'maria santos');

    expect(fn () => svc()->assertTimeChangeAllowed($e2, $e2->starts_at->copy()->setTime(10, 0), $e2->ends_at->copy()->setTime(12, 30)))
        ->toThrow(RosterRuleViolation::class, 'already serving');
});

test('moving an event onto the same guest name in a different branch is allowed', function () {
    [$e1, $r1] = rosterEvent('09:00', '11:00');
    [$e2, $r2] = rosterEvent('12:00', '14:00');
    expect($e2->branch_id)->not->toBe($e1->branch_id);
    svc()->assignGuest($e1, $r1, 'Maria Santos');
    svc()->assignGuest($e2, $r2, 'Maria Santos');

    svc()->assertTimeChangeAllowed($e2, $e2->starts_at->copy()->setTime(10, 0), $e2->ends_at->copy()->setTime(12, 30));

    expect(true)->toBeTrue();
});

test('slot cannot be reduced below the guests serving', function () {
    [$event, $role] = rosterEvent(quantity: 2);
    svc()->assignGuest($event, $role, 'Guest One');
    svc()->assignGuest($event, $role, 'Guest Two');

    expect(fn () => svc()->assertSlotChangesAllowed($event, [$role->id => 1]))
        ->toThrow(RosterRuleViolation::class, 'already filled');
});
