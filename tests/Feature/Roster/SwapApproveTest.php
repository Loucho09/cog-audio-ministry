<?php

use App\Enums\AvailabilityPart;
use App\Enums\DutyStatus;
use App\Enums\SwapStatus;
use App\Exceptions\RosterRuleViolation;
use App\Models\DutyAssignment;
use App\Models\DutySwap;
use App\Models\MemberUnavailability;
use App\Models\User;

test('approve swaps the duty to the claimer', function () {
    [$swap, $duty, $claimer] = claimedSwap();
    $admin = branchAdminFor($duty);

    $new = swapSvc()->approve($swap, $admin);

    expect($duty->fresh()->status)->toBe(DutyStatus::Swapped)
        ->and($new->user_id)->toBe($claimer->id)
        ->and($new->status)->toBe(DutyStatus::Accepted)
        ->and($new->assigned_by)->toBe($admin->id)
        ->and($new->event_id)->toBe($duty->event_id)
        ->and($new->duty_role_id)->toBe($duty->duty_role_id)
        ->and($new->branch_id)->toBe($duty->branch_id)
        ->and($new->responded_at)->not->toBeNull()
        ->and($swap->fresh()->status)->toBe(SwapStatus::Approved)
        ->and($swap->fresh()->decided_by)->toBe($admin->id)
        ->and($swap->fresh()->decided_at)->not->toBeNull();
});

test('active fill count is unchanged and within capacity after approve', function () {
    [$swap, $duty] = claimedSwap();
    $before = activeCount($duty);

    swapSvc()->approve($swap, branchAdminFor($duty));

    expect(activeCount($duty))->toBe($before)->toBe(1)
        ->and(activeCount($duty))->toBeLessThanOrEqual($duty->event->dutySlots()->first()->quantity);
});

test('reject returns the duty to accepted', function () {
    [$swap, $duty] = claimedSwap();
    $admin = branchAdminFor($duty);

    swapSvc()->reject($swap, $admin);

    expect($swap->fresh()->status)->toBe(SwapStatus::Rejected)
        ->and($swap->fresh()->decided_by)->toBe($admin->id)
        ->and($duty->fresh()->status)->toBe(DutyStatus::Accepted);
});

test('open swap can be rejected but not approved', function () {
    [$swap, $duty] = openSwap();
    $admin = branchAdminFor($duty);

    expect(fn () => swapSvc()->approve($swap, $admin))->toThrow(RosterRuleViolation::class);

    swapSvc()->reject($swap, $admin);
    expect($swap->fresh()->status)->toBe(SwapStatus::Rejected)
        ->and($duty->fresh()->status)->toBe(DutyStatus::Accepted);
});

test('expired swap cannot be approved or rejected', function () {
    [$swap, $duty] = claimedSwap();
    $admin = branchAdminFor($duty);
    $duty->event->update(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);

    expect(fn () => swapSvc()->approve($swap, $admin))
        ->toThrow(RosterRuleViolation::class, 'This swap expired because the service already started.');
    expect(fn () => swapSvc()->reject($swap, $admin))
        ->toThrow(RosterRuleViolation::class, 'This swap expired because the service already started.');
    expect($swap->fresh()->status)->toBe(SwapStatus::Claimed);
});

test('approve fails when claimer became unavailable', function () {
    [$swap, $duty, $claimer] = claimedSwap();
    MemberUnavailability::create([
        'user_id' => $claimer->id,
        'branch_id' => $claimer->branch_id,
        'date' => $duty->event->starts_at->toDateString(),
        'part' => AvailabilityPart::Am,
    ]);

    expect(fn () => swapSvc()->approve($swap, branchAdminFor($duty)))
        ->toThrow(RosterRuleViolation::class, 'unavailable');
    expect($duty->fresh()->status)->toBe(DutyStatus::SwapRequested)
        ->and($swap->fresh()->status)->toBe(SwapStatus::Claimed);
});

test('approve fails when claimer got an overlapping duty', function () {
    [$swap, $duty, $claimer] = claimedSwap();
    [$clash, $role] = rosterEvent('10:00', '12:00', 1, null, $duty->event);
    DutyAssignment::factory()->create([
        'event_id' => $clash->id,
        'duty_role_id' => $role->id,
        'user_id' => $claimer->id,
        'status' => DutyStatus::Accepted,
    ]);

    expect(fn () => swapSvc()->approve($swap, branchAdminFor($duty)))
        ->toThrow(RosterRuleViolation::class, 'already serving');
});

test('admin of another branch cannot decide', function () {
    [$swap, $duty] = claimedSwap();
    [$otherEvent] = rosterEvent();
    $outsider = User::factory()->branchAdmin($otherEvent->branch)->create();

    expect(swapSvc()->canDecide($outsider, $swap))->toBeFalse();
    expect(fn () => swapSvc()->approve($swap, $outsider))
        ->toThrow(RosterRuleViolation::class, "You can't approve swaps for this branch.");
    expect(fn () => swapSvc()->reject($swap, $outsider))->toThrow(RosterRuleViolation::class);
});

test('plain member cannot decide', function () {
    [$swap, $duty] = claimedSwap();

    expect(fn () => swapSvc()->approve($swap, rosterMember($duty->event)))
        ->toThrow(RosterRuleViolation::class);
});

test('super admin can approve', function () {
    [$swap, $duty, $claimer] = claimedSwap();

    $new = swapSvc()->approve($swap, User::factory()->superAdmin()->create());

    expect($new->user_id)->toBe($claimer->id);
});

test('offerer admin cannot self-approve when another admin exists', function () {
    [$event, $role] = rosterEvent('09:30', '11:00');
    $adminA = User::factory()->branchAdmin($event->branch)->create();
    $adminB = User::factory()->branchAdmin($event->branch)->create();
    $duty = DutyAssignment::factory()->create([
        'event_id' => $event->id,
        'duty_role_id' => $role->id,
        'user_id' => $adminA->id,
        'status' => DutyStatus::Accepted,
    ]);
    $swap = swapSvc()->offer($duty, $adminA);
    swapSvc()->claim($swap, rosterMember($event));

    expect(fn () => swapSvc()->approve($swap->fresh(), $adminA))
        ->toThrow(RosterRuleViolation::class, 'Another admin of your branch needs to approve this.');
    expect(fn () => swapSvc()->reject($swap->fresh(), $adminA))->toThrow(RosterRuleViolation::class);

    expect(swapSvc()->approve($swap->fresh(), $adminB)->status)->toBe(DutyStatus::Accepted);
});

test('remove deletes the assignment and cascades its live swap', function () {
    [$swap, $duty] = claimedSwap();

    svc()->remove($duty);

    // The FK cascade deletes the swap rows with their assignment.
    expect(DutySwap::find($swap->id))->toBeNull()
        ->and(DutyAssignment::find($duty->id))->toBeNull();
});

test('remove rejects a swapped row', function () {
    [$swap, $duty] = claimedSwap();
    swapSvc()->approve($swap, branchAdminFor($duty));

    expect(fn () => svc()->remove($duty->fresh()))
        ->toThrow(RosterRuleViolation::class, "This duty was swapped and can't be removed.");
    expect(DutyAssignment::find($duty->id))->not->toBeNull();
});
