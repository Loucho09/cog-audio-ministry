<?php

use App\Enums\AvailabilityPart;
use App\Enums\DutyStatus;
use App\Enums\SwapStatus;
use App\Exceptions\RosterRuleViolation;
use App\Models\Branch;
use App\Models\DutyAssignment;
use App\Models\DutySwap;
use App\Models\MemberUnavailability;
use App\Models\User;
use App\Services\DutySwapService;

/** @return array{0: DutyAssignment, 1: User} accepted duty and its owner */
function dashSwapDuty(string $start = '09:30', string $end = '11:00'): array
{
    [$event, $role] = rosterEvent($start, $end);
    $owner = User::factory()->member($event->branch)->create();
    $duty = DutyAssignment::factory()->create([
        'event_id' => $event->id,
        'duty_role_id' => $role->id,
        'user_id' => $owner->id,
        'status' => DutyStatus::Accepted,
    ]);

    return [$duty, $owner];
}

function dashOpenSwap(DutyAssignment $duty): DutySwap
{
    return app(DutySwapService::class)->offer($duty, $duty->user);
}

it('lets the owner offer a swap through the route', function () {
    [$duty, $owner] = dashSwapDuty();

    $this->actingAs($owner)->post(route('swaps.store', $duty))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'Your swap is posted. Your admin will confirm once someone claims it.');

    expect($duty->fresh()->status)->toBe(DutyStatus::SwapRequested)
        ->and(DutySwap::where('duty_assignment_id', $duty->id)->where('status', SwapStatus::Open)->count())->toBe(1);

    $this->get('/dashboard')->assertOk()->assertSee('Swap offered')->assertSee('Cancel swap');
});

it('stops someone else from offering a duty that is not theirs', function () {
    [$duty] = dashSwapDuty();
    $other = User::factory()->member($duty->event->branch)->create();

    $this->actingAs($other)->post(route('swaps.store', $duty))->assertForbidden();
});

it('lets a same-branch member claim through the route', function () {
    [$duty] = dashSwapDuty();
    $swap = dashOpenSwap($duty);
    $claimer = User::factory()->member($duty->event->branch)->create();

    $this->actingAs($claimer)->get('/dashboard')->assertOk()
        ->assertSee('Open swaps in your branch')
        ->assertSee($duty->user->name);

    $this->post(route('swaps.claim', $swap))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'Thanks for stepping in! Waiting for your admin to approve.');

    expect($swap->fresh()->status)->toBe(SwapStatus::Claimed)
        ->and($swap->fresh()->claimed_by)->toBe($claimer->id);

    $this->actingAs($duty->user)->get('/dashboard')->assertSee('Claimed by '.$claimer->name, false);
});

it('lets the offerer cancel through the route', function () {
    [$duty, $owner] = dashSwapDuty();
    $swap = dashOpenSwap($duty);

    $this->actingAs($owner)->post(route('swaps.cancel', $swap))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', "Swap cancelled. You're still on this duty.");

    expect($swap->fresh()->status)->toBe(SwapStatus::Cancelled)
        ->and($duty->fresh()->status)->toBe(DutyStatus::Accepted);
});

it('forbids another member from cancelling', function () {
    [$duty] = dashSwapDuty();
    $swap = dashOpenSwap($duty);
    $other = User::factory()->member($duty->event->branch)->create();

    $this->actingAs($other)->post(route('swaps.cancel', $swap))->assertForbidden();
    expect($swap->fresh()->status)->toBe(SwapStatus::Open);
});

it('forbids a member of another branch from claiming', function () {
    [$duty] = dashSwapDuty();
    $swap = dashOpenSwap($duty);
    $outsider = User::factory()->member(Branch::factory()->create())->create();

    $this->actingAs($outsider)->post(route('swaps.claim', $swap))->assertForbidden();
    expect($swap->fresh()->status)->toBe(SwapStatus::Open);
});

it('forbids the offerer from claiming their own swap', function () {
    [$duty, $owner] = dashSwapDuty();
    $swap = dashOpenSwap($duty);

    $this->actingAs($owner)->post(route('swaps.claim', $swap))->assertForbidden();
});

it('flashes an error when a rule blocks the claim', function () {
    [$duty] = dashSwapDuty();
    $swap = dashOpenSwap($duty);
    $claimer = User::factory()->member($duty->event->branch)->create();
    [$overlap] = rosterEvent('10:00', '12:00', 1, null, $duty->event);
    DutyAssignment::factory()->create([
        'event_id' => $overlap->id,
        'user_id' => $claimer->id,
        'status' => DutyStatus::Accepted,
    ]);

    $this->actingAs($claimer)->post(route('swaps.claim', $swap))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error', 'You are already serving at that time.');

    expect($swap->fresh()->status)->toBe(SwapStatus::Open);
});

it('hides own offers and ineligible swaps from the open list', function () {
    [$duty, $owner] = dashSwapDuty();
    dashOpenSwap($duty);
    $viewer = User::factory()->member($duty->event->branch)->create();

    $this->actingAs($owner)->get('/dashboard')->assertSee('No open swaps right now.');

    $this->actingAs($viewer)->get('/dashboard')->assertDontSee('No open swaps right now.');

    MemberUnavailability::create([
        'branch_id' => $viewer->branch_id,
        'user_id' => $viewer->id,
        'date' => $duty->event->starts_at->toDateString(),
        'part' => AvailabilityPart::WholeDay,
    ]);

    $this->get('/dashboard')->assertOk()->assertSee('No open swaps right now.');
});

it('redirects guests to login', function () {
    [$duty] = dashSwapDuty();
    $swap = dashOpenSwap($duty);

    $this->post(route('swaps.store', $duty))->assertRedirect(route('login'));
    $this->post(route('swaps.claim', $swap))->assertRedirect(route('login'));
    $this->post(route('swaps.cancel', $swap))->assertRedirect(route('login'));
});

it('shows an expired notice when the swap event has started', function () {
    [$duty, $owner] = dashSwapDuty();
    dashOpenSwap($duty);
    $duty->event->update(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);

    $this->actingAs($owner)->get('/dashboard')->assertOk()
        ->assertSee("No one took this swap in time, so you're still on this duty.")
        ->assertDontSee('Cancel swap');
});

it('lists the claimer their pending claim', function () {
    [$duty] = dashSwapDuty();
    $swap = dashOpenSwap($duty);
    $claimer = User::factory()->member($duty->event->branch)->create();
    app(DutySwapService::class)->claim($swap, $claimer);

    $this->actingAs($claimer)->get('/dashboard')->assertOk()
        ->assertSee('You offered to take')
        ->assertSee($duty->dutyRole->name)
        ->assertSee('Waiting for your admin.');
});

it('agrees with claim() on overlap and AM-unavailable cases', function () {
    [$duty] = dashSwapDuty('09:30', '11:00');
    $swap = dashOpenSwap($duty);
    $branch = $duty->event->branch;

    // Overlapping active duty.
    $busy = User::factory()->member($branch)->create();
    [$otherEvent, $otherRole] = rosterEvent('10:00', '12:00', 1, null, $duty->event);
    DutyAssignment::factory()->create([
        'event_id' => $otherEvent->id, 'duty_role_id' => $otherRole->id,
        'user_id' => $busy->id, 'status' => DutyStatus::Accepted,
    ]);

    // AM-unavailable.
    $away = User::factory()->member($branch)->create();
    MemberUnavailability::create([
        'branch_id' => $branch->id, 'user_id' => $away->id,
        'date' => $duty->event->starts_at->toDateString(), 'part' => AvailabilityPart::Am,
    ]);

    // PM-unavailable does not block an AM event.
    $pmAway = User::factory()->member($branch)->create();
    MemberUnavailability::create([
        'branch_id' => $branch->id, 'user_id' => $pmAway->id,
        'date' => $duty->event->starts_at->toDateString(), 'part' => AvailabilityPart::Pm,
    ]);

    $free = User::factory()->member($branch)->create();

    foreach ([$busy, $away] as $blocked) {
        $this->actingAs($blocked)->get('/dashboard')->assertSee('No open swaps right now.');
        expect(fn () => app(DutySwapService::class)->claim($swap, $blocked))->toThrow(RosterRuleViolation::class);
    }

    foreach ([$pmAway, $free] as $ok) {
        $this->actingAs($ok)->get('/dashboard')->assertDontSee('No open swaps right now.');
    }

    expect(app(DutySwapService::class)->claim($swap, $pmAway)->status)->toBe(SwapStatus::Claimed);
});
