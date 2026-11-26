<?php

use App\Enums\DutyStatus;
use App\Enums\SwapStatus;
use App\Exceptions\RosterRuleViolation;
use App\Models\Branch;
use App\Models\DutyAssignment;
use App\Models\DutySwap;
use App\Models\MemberUnavailability;
use App\Models\User;

test('roster service refuses to assign a branch admin', function () {
    [$event, $role] = rosterEvent();
    $admin = User::factory()->branchAdmin($event->branch)->create();

    expect(fn () => svc()->assign($event, $role, $admin))
        ->toThrow(RosterRuleViolation::class, 'Admins manage the roster');
    expect(DutyAssignment::count())->toBe(0);
});

test('roster service refuses to assign a super admin', function () {
    [$event, $role] = rosterEvent();
    $super = User::factory()->superAdmin()->create(['branch_id' => $event->branch_id]);

    expect(fn () => svc()->assign($event, $role, $super))->toThrow(RosterRuleViolation::class);
});

test('swap service refuses an admin claiming a swap', function () {
    [$swap, $duty] = openSwap();
    $admin = User::factory()->branchAdmin($duty->event->branch)->create();

    expect(fn () => swapSvc()->claim($swap, $admin))
        ->toThrow(RosterRuleViolation::class, 'Admins manage the roster');
    expect($swap->fresh()->status)->toBe(SwapStatus::Open);
});

test('claim route rejects an admin', function () {
    [$swap, $duty] = openSwap();
    $admin = User::factory()->branchAdmin($duty->event->branch)->create();

    $this->actingAs($admin)->post(route('swaps.claim', $swap))->assertForbidden();
    expect($swap->fresh()->status)->toBe(SwapStatus::Open);
});

test('accept, decline and offer routes reject an admin even on their own row', function () {
    [$event, $role] = rosterEvent();
    $admin = User::factory()->branchAdmin($event->branch)->create();
    $duty = DutyAssignment::factory()->create([
        'event_id' => $event->id, 'duty_role_id' => $role->id, 'user_id' => $admin->id, 'status' => DutyStatus::Assigned,
    ]);

    $this->actingAs($admin)->post(route('duties.accept', $duty))->assertForbidden();
    $this->post(route('duties.decline', $duty))->assertForbidden();
    $this->post(route('swaps.store', $duty))->assertForbidden();
    expect($duty->fresh()->status)->toBe(DutyStatus::Assigned);
});

test('availability routes reject an admin', function () {
    $admin = User::factory()->branchAdmin(Branch::factory()->create())->create();

    $this->actingAs($admin)->post(route('availability.store'), [
        'date' => now()->addDays(3)->toDateString(), 'part' => 'whole_day',
    ])->assertForbidden();
    expect(MemberUnavailability::count())->toBe(0);
});

test('policies deny admins and allow members', function () {
    [$swap, $duty] = openSwap();
    $admin = User::factory()->branchAdmin($duty->event->branch)->create();
    $member = rosterMember($duty->event);

    expect($admin->can('create', MemberUnavailability::class))->toBeFalse()
        ->and($member->can('create', MemberUnavailability::class))->toBeTrue()
        ->and($admin->can('claim', $swap))->toBeFalse()
        ->and($member->can('claim', $swap))->toBeTrue();
});

test('demo roster never assigns admins', function () {
    $this->seed();

    $adminIds = User::role(['branch-admin', 'super-admin'])->pluck('id');

    expect(DutyAssignment::whereIn('user_id', $adminIds)->count())->toBe(0)
        ->and(DutySwap::whereIn('offered_by', $adminIds)->orWhereIn('claimed_by', $adminIds)->count())->toBe(0);
});
