<?php

use App\Enums\DutyStatus;
use App\Enums\SwapStatus;
use App\Exceptions\RosterRuleViolation;
use App\Models\DutyAssignment;
use App\Models\DutySwap;

function offerDuty(DutyStatus $status = DutyStatus::Accepted, string $start = '09:00'): DutyAssignment
{
    [$event, $role] = rosterEvent($start, '11:00');

    return DutyAssignment::factory()->create([
        'event_id' => $event->id,
        'duty_role_id' => $role->id,
        'user_id' => rosterMember($event)->id,
        'status' => $status,
    ]);
}

test('owner offers an accepted duty', function () {
    $duty = offerDuty();

    $swap = swapSvc()->offer($duty, $duty->user);

    expect($swap->status)->toBe(SwapStatus::Open)
        ->and($swap->branch_id)->toBe($duty->branch_id)
        ->and($swap->offered_by)->toBe($duty->user_id)
        ->and($swap->claimed_by)->toBeNull()
        ->and($duty->fresh()->status)->toBe(DutyStatus::SwapRequested);
});

test('another member cannot offer', function () {
    $duty = offerDuty();
    $other = rosterMember($duty->event);

    expect(fn () => swapSvc()->offer($duty, $other))->toThrow(RosterRuleViolation::class);
    expect(DutySwap::count())->toBe(0);
});

test('guest row cannot be offered', function () {
    [$event, $role] = rosterEvent();
    $guest = DutyAssignment::factory()->guest()->create(['event_id' => $event->id, 'duty_role_id' => $role->id]);

    expect(fn () => swapSvc()->offer($guest, rosterMember($event)))->toThrow(RosterRuleViolation::class);
});

test('only accepted duties can be offered', function (DutyStatus $status) {
    $duty = offerDuty($status);

    expect(fn () => swapSvc()->offer($duty, $duty->user))
        ->toThrow(RosterRuleViolation::class, 'Accept the duty first, or decline it.');
})->with([DutyStatus::Assigned, DutyStatus::Declined, DutyStatus::Swapped]);

test('cannot offer twice', function () {
    $duty = offerDuty();
    swapSvc()->offer($duty, $duty->user);

    expect(fn () => swapSvc()->offer($duty, $duty->user))->toThrow(RosterRuleViolation::class);
    expect(DutySwap::count())->toBe(1);
});

test('cannot offer after the event started', function () {
    $duty = offerDuty();
    $duty->event->update(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);

    expect(fn () => swapSvc()->offer($duty, $duty->user))->toThrow(RosterRuleViolation::class);
});

test('offerer cancels an open swap and the duty is accepted again', function () {
    $duty = offerDuty();
    $swap = swapSvc()->offer($duty, $duty->user);

    $result = swapSvc()->cancel($swap, $duty->user);

    expect($result->status)->toBe(SwapStatus::Cancelled)
        ->and($result->decided_at)->toBeNull()
        ->and($duty->fresh()->status)->toBe(DutyStatus::Accepted);
});

test('offerer cancels a claimed swap', function () {
    $duty = offerDuty();
    $swap = swapSvc()->offer($duty, $duty->user);
    $swap->update(['status' => SwapStatus::Claimed, 'claimed_by' => rosterMember($duty->event)->id, 'claimed_at' => now()]);

    swapSvc()->cancel($swap, $duty->user);

    expect($swap->fresh()->status)->toBe(SwapStatus::Cancelled)
        ->and($duty->fresh()->status)->toBe(DutyStatus::Accepted);
});

test('only the offerer can cancel', function () {
    $duty = offerDuty();
    $swap = swapSvc()->offer($duty, $duty->user);

    expect(fn () => swapSvc()->cancel($swap, rosterMember($duty->event)))->toThrow(RosterRuleViolation::class);
    expect($swap->fresh()->status)->toBe(SwapStatus::Open);
});

test('cannot cancel a finished swap or one for a started event', function () {
    $duty = offerDuty();
    $swap = swapSvc()->offer($duty, $duty->user);
    $swap->update(['status' => SwapStatus::Approved]);
    expect(fn () => swapSvc()->cancel($swap, $duty->user))->toThrow(RosterRuleViolation::class);

    $swap->update(['status' => SwapStatus::Open]);
    $duty->event->update(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
    expect(fn () => swapSvc()->cancel($swap, $duty->user))->toThrow(RosterRuleViolation::class);
});
