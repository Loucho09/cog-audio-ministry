<?php

use App\Enums\DutyStatus;
use App\Enums\SwapStatus;
use App\Exceptions\RosterRuleViolation;
use App\Models\DutyAssignment;
use App\Models\DutySwap;

function swapRow(DutyAssignment $assignment, SwapStatus $status = SwapStatus::Open): DutySwap
{
    return DutySwap::create([
        'branch_id' => $assignment->branch_id,
        'duty_assignment_id' => $assignment->id,
        'offered_by' => $assignment->user_id,
        'status' => $status,
    ]);
}

function dutyWith(DutyStatus $status, $event, $role, $member = null): DutyAssignment
{
    return DutyAssignment::factory()->create([
        'event_id' => $event->id,
        'duty_role_id' => $role->id,
        'user_id' => ($member ?? rosterMember($event))->id,
        'status' => $status,
    ]);
}

test('enum labels', function () {
    expect(DutyStatus::SwapRequested->label())->toBe('Swap offered')
        ->and(DutyStatus::Swapped->label())->toBe('Swapped')
        ->and(SwapStatus::Open->label())->toBe('Open')
        ->and(SwapStatus::Claimed->label())->toBe('Claimed')
        ->and(SwapStatus::Approved->label())->toBe('Approved')
        ->and(SwapStatus::Rejected->label())->toBe('Rejected')
        ->and(SwapStatus::Cancelled->label())->toBe('Cancelled');
});

test('active scope excludes swapped but keeps swap requested', function () {
    [$event, $role] = rosterEvent(quantity: 3);
    dutyWith(DutyStatus::Swapped, $event, $role);
    dutyWith(DutyStatus::SwapRequested, $event, $role);
    dutyWith(DutyStatus::Declined, $event, $role);

    expect(DutyAssignment::active()->count())->toBe(1);
});

test('capacity ignores swapped rows and counts swap requested rows', function () {
    [$event, $role] = rosterEvent(quantity: 1);
    dutyWith(DutyStatus::Swapped, $event, $role);
    svc()->assign($event, $role, rosterMember($event));

    [$event2, $role2] = rosterEvent(quantity: 1);
    dutyWith(DutyStatus::SwapRequested, $event2, $role2);

    expect(fn () => svc()->assign($event2, $role2, rosterMember($event2)))
        ->toThrow(RosterRuleViolation::class, 'already full');
});

test('member overlap ignores swapped rows and counts swap requested rows', function () {
    [$e1, $r1] = rosterEvent('09:00', '11:00');
    [$e2, $r2] = rosterEvent('10:00', '12:00', like: $e1);
    $swappedAway = rosterMember($e1);
    $stillOn = rosterMember($e1);
    dutyWith(DutyStatus::Swapped, $e1, $r1, $swappedAway);
    dutyWith(DutyStatus::SwapRequested, $e1, $r1, $stillOn);

    expect(svc()->assign($e2, $r2, $swappedAway))->toBeInstanceOf(DutyAssignment::class)
        ->and(fn () => svc()->assign($e2, $r2, $stillOn))
        ->toThrow(RosterRuleViolation::class, 'already serving');
});

test('slot reduction ignores swapped rows and counts swap requested rows', function () {
    [$event, $role] = rosterEvent(quantity: 2);
    dutyWith(DutyStatus::Swapped, $event, $role);
    dutyWith(DutyStatus::SwapRequested, $event, $role);

    svc()->assertSlotChangesAllowed($event, [$role->id => 1]);

    expect(fn () => svc()->assertSlotChangesAllowed($event, [$role->id => 0]))
        ->toThrow(RosterRuleViolation::class, 'already filled');
});

test('time change rule ignores swapped rows and counts swap requested rows', function () {
    [$e1, $r1] = rosterEvent('09:00', '11:00');
    [$e2, $r2] = rosterEvent('12:00', '14:00', like: $e1);
    $member = rosterMember($e1);
    dutyWith(DutyStatus::Swapped, $e1, $r1, $member);
    dutyWith(DutyStatus::Accepted, $e2, $r2, $member);

    svc()->assertTimeChangeAllowed($e2, $e2->starts_at->copy()->setTime(10, 0), $e2->ends_at->copy()->setTime(12, 30));

    DutyAssignment::query()->where('event_id', $e1->id)->update(['status' => DutyStatus::SwapRequested]);

    expect(fn () => svc()->assertTimeChangeAllowed($e2, $e2->starts_at->copy()->setTime(10, 0), $e2->ends_at->copy()->setTime(12, 30)))
        ->toThrow(RosterRuleViolation::class, 'already serving');
});

test('dashboard renders a swap requested duty', function () {
    [$event, $role] = rosterEvent();
    $member = rosterMember($event);
    dutyWith(DutyStatus::SwapRequested, $event, $role, $member);

    $this->actingAs($member)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Swap offered');
});

test('live scope keeps open and claimed on future events only', function () {
    [$event, $role] = rosterEvent(quantity: 5);
    $open = swapRow(dutyWith(DutyStatus::SwapRequested, $event, $role), SwapStatus::Open);
    $claimed = swapRow(dutyWith(DutyStatus::SwapRequested, $event, $role), SwapStatus::Claimed);
    foreach ([SwapStatus::Approved, SwapStatus::Rejected, SwapStatus::Cancelled] as $closed) {
        swapRow(dutyWith(DutyStatus::Accepted, $event, $role), $closed);
    }

    [$past, $pastRole] = rosterEvent(quantity: 1);
    $started = swapRow(dutyWith(DutyStatus::SwapRequested, $past, $pastRole));
    $past->update(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);

    expect(DutySwap::live()->pluck('id')->all())->toEqualCanonicalizing([$open->id, $claimed->id])
        ->and($started->fresh()->assignment->liveSwap())->toBeNull()
        ->and($open->assignment->liveSwap()?->id)->toBe($open->id);
});
