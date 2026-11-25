<?php

use App\Enums\AvailabilityPart;
use App\Enums\DutyStatus;
use App\Enums\SwapStatus;
use App\Exceptions\RosterRuleViolation;
use App\Models\DutyAssignment;
use App\Models\DutySwap;
use App\Models\MemberUnavailability;
use App\Models\User;

test('same-branch member claims an open swap', function () {
    [$swap, $duty] = openSwap();
    $claimer = rosterMember($duty->event);

    $result = swapSvc()->claim($swap, $claimer);

    expect($result->status)->toBe(SwapStatus::Claimed)
        ->and($result->claimed_by)->toBe($claimer->id)
        ->and($result->claimed_at)->not->toBeNull()
        ->and($duty->fresh()->status)->toBe(DutyStatus::SwapRequested);
});

test('branch admin of the branch can claim', function () {
    [$swap, $duty] = openSwap();
    $admin = User::factory()->branchAdmin($duty->event->branch)->create();

    expect(swapSvc()->claim($swap, $admin)->status)->toBe(SwapStatus::Claimed);
});

test('second claimer is rejected', function () {
    [$swap, $duty] = openSwap();
    swapSvc()->claim($swap, rosterMember($duty->event));
    $second = rosterMember($duty->event);

    expect(fn () => swapSvc()->claim($swap, $second))
        ->toThrow(RosterRuleViolation::class, 'Someone already took this swap.');
    expect($swap->fresh()->claimed_by)->not->toBe($second->id);
});

test('offerer cannot claim their own swap', function () {
    [$swap, $duty] = openSwap();

    expect(fn () => swapSvc()->claim($swap, $duty->user))->toThrow(RosterRuleViolation::class);
});

test('member of another branch is rejected', function () {
    [$swap] = openSwap();
    [$otherEvent] = rosterEvent();

    expect(fn () => swapSvc()->claim($swap, rosterMember($otherEvent)))
        ->toThrow(RosterRuleViolation::class, 'different branch');
    expect($swap->fresh()->status)->toBe(SwapStatus::Open);
});

test('user without a duty role is rejected', function () {
    [$swap, $duty] = openSwap();
    $plain = User::factory()->create(['branch_id' => $duty->event->branch_id]);

    expect(fn () => swapSvc()->claim($swap, $plain))->toThrow(RosterRuleViolation::class);
});

test('overlapping active duty is rejected', function () {
    [$swap, $duty] = openSwap();
    $claimer = rosterMember($duty->event);
    [$clash, $role] = rosterEvent('10:00', '12:00', 1, null, $duty->event);
    DutyAssignment::factory()->create([
        'event_id' => $clash->id,
        'duty_role_id' => $role->id,
        'user_id' => $claimer->id,
        'status' => DutyStatus::Accepted,
    ]);

    expect(fn () => swapSvc()->claim($swap, $claimer))
        ->toThrow(RosterRuleViolation::class, 'already serving');
});

test('unavailable AM entry blocks a 9:30 event', function () {
    [$swap, $duty] = openSwap('09:30', '11:00');
    $claimer = rosterMember($duty->event);
    MemberUnavailability::create([
        'user_id' => $claimer->id,
        'branch_id' => $claimer->branch_id,
        'date' => $duty->event->starts_at->toDateString(),
        'part' => AvailabilityPart::Am,
    ]);

    expect(fn () => swapSvc()->claim($swap, $claimer))
        ->toThrow(RosterRuleViolation::class, 'unavailable');
});

test('started event is rejected', function () {
    [$swap, $duty] = openSwap();
    $duty->event->update(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);

    expect(fn () => swapSvc()->claim($swap, rosterMember($duty->event)))->toThrow(RosterRuleViolation::class);
});

test('cancelled swap cannot be claimed', function () {
    [$swap, $duty] = openSwap();
    swapSvc()->cancel($swap, $duty->user);

    expect(fn () => swapSvc()->claim($swap, rosterMember($duty->event)))
        ->toThrow(RosterRuleViolation::class, 'no longer available');
});

test('offerer cancels a claimed swap and the duty is accepted again', function () {
    [$swap, $duty] = openSwap();
    swapSvc()->claim($swap, rosterMember($duty->event));

    swapSvc()->cancel($swap, $duty->user);

    expect($swap->fresh()->status)->toBe(SwapStatus::Cancelled)
        ->and($duty->fresh()->status)->toBe(DutyStatus::Accepted);
});

test('claimableBy lists live open swaps in the branch not offered by the user', function () {
    [$swap, $duty] = openSwap();
    $viewer = rosterMember($duty->event);
    [$otherSwap] = openSwap();
    [$claimed, $claimedDuty] = openSwap();
    swapSvc()->claim($claimed, rosterMember($claimedDuty->event));

    expect(DutySwap::claimableBy($viewer)->pluck('id')->all())->toBe([$swap->id])
        ->and(DutySwap::claimableBy($duty->user)->count())->toBe(0);
});
