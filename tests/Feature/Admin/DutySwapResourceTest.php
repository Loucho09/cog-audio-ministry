<?php

use App\Enums\AvailabilityPart;
use App\Enums\DutyStatus;
use App\Enums\SwapStatus;
use App\Filament\Resources\DutySwaps\DutySwapResource;
use App\Filament\Resources\DutySwaps\Pages\ListDutySwaps;
use App\Models\Branch;
use App\Models\DutyAssignment;
use App\Models\DutyRole;
use App\Models\DutySwap;
use App\Models\Event;
use App\Models\MemberUnavailability;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    $this->branchA = Branch::factory()->create();
    $this->branchB = Branch::factory()->create();
});

function swapFixture(Branch $branch, SwapStatus $status = SwapStatus::Claimed, ?User $offerer = null, ?User $claimer = null, bool $past = false): DutySwap
{
    $event = Event::factory()->create(['branch_id' => $branch->id] + ($past ? [
        'starts_at' => now()->subDay(), 'ends_at' => now()->subDay()->addHours(2),
    ] : []));
    $role = DutyRole::factory()->create(['branch_id' => $branch->id]);
    $offerer ??= User::factory()->member($branch)->create();
    $assignment = DutyAssignment::factory()->create([
        'event_id' => $event->id, 'branch_id' => $branch->id, 'duty_role_id' => $role->id,
        'user_id' => $offerer->id, 'status' => DutyStatus::SwapRequested,
    ]);
    $claimed = $status === SwapStatus::Claimed;
    $claimer = $claimed ? ($claimer ?? User::factory()->member($branch)->create()) : null;

    return DutySwap::create([
        'branch_id' => $branch->id,
        'duty_assignment_id' => $assignment->id,
        'offered_by' => $offerer->id,
        'claimed_by' => $claimer?->id,
        'status' => $status,
        'claimed_at' => $claimed ? now() : null,
    ]);
}

it('shows a branch-admin only own-branch swaps', function () {
    $mine = swapFixture($this->branchA);
    $foreign = swapFixture($this->branchB);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(ListDutySwaps::class)
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$foreign]);
});

it('lets super-admin see every branch', function () {
    $a = swapFixture($this->branchA);
    $b = swapFixture($this->branchB);
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(ListDutySwaps::class)->assertCanSeeTableRecords([$a, $b]);
});

it('approves a claimed swap from the table', function () {
    $swap = swapFixture($this->branchA);
    $admin = User::factory()->branchAdmin($this->branchA)->create();
    $this->actingAs($admin);

    Livewire::test(ListDutySwaps::class)
        ->callTableAction('approve', $swap)
        ->assertNotified('Swap approved');

    expect($swap->fresh()->status)->toBe(SwapStatus::Approved)
        ->and($swap->assignment->fresh()->status)->toBe(DutyStatus::Swapped)
        ->and(DutyAssignment::where('user_id', $swap->claimed_by)->where('status', DutyStatus::Accepted)->count())->toBe(1);
});

it('rejects an open swap from the table', function () {
    $swap = swapFixture($this->branchA, SwapStatus::Open);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(ListDutySwaps::class)
        ->filterTable('status', 'open')
        ->assertTableActionHidden('approve', $swap)
        ->callTableAction('reject', $swap)
        ->assertNotified('Swap rejected');

    expect($swap->fresh()->status)->toBe(SwapStatus::Rejected)
        ->and($swap->assignment->fresh()->status)->toBe(DutyStatus::Accepted);
});

it('hides actions on started events', function () {
    $swap = swapFixture($this->branchA, SwapStatus::Claimed, past: true);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(ListDutySwaps::class)
        ->assertTableActionHidden('approve', $swap)
        ->assertTableActionHidden('reject', $swap);
});

it('does not let another branch admin see or approve a swap', function () {
    $swap = swapFixture($this->branchA);
    $this->actingAs(User::factory()->branchAdmin($this->branchB)->create());

    Livewire::test(ListDutySwaps::class)->assertCanNotSeeTableRecords([$swap]);

    try {
        Livewire::test(ListDutySwaps::class)->callTableAction('approve', $swap);
    } catch (Throwable) {
        // the record is outside the admin's query, so the action cannot run
    }

    expect($swap->fresh()->status)->toBe(SwapStatus::Claimed);
});

it('blocks self-approval when the branch has two admins', function () {
    $offerAdmin = User::factory()->branchAdmin($this->branchA)->create();
    User::factory()->branchAdmin($this->branchA)->create();
    $swap = swapFixture($this->branchA, SwapStatus::Claimed, $offerAdmin);
    $this->actingAs($offerAdmin);

    Livewire::test(ListDutySwaps::class)
        ->assertTableActionHidden('approve', $swap)
        ->assertTableActionHidden('reject', $swap);

    expect($swap->fresh()->status)->toBe(SwapStatus::Claimed);
});

it('allows self-approval for the only admin of a branch', function () {
    $admin = User::factory()->branchAdmin($this->branchA)->create();
    $swap = swapFixture($this->branchA, SwapStatus::Claimed, $admin);
    $this->actingAs($admin);

    Livewire::test(ListDutySwaps::class)->assertTableActionVisible('approve', $swap);
});

it('shows a danger notification when a rule fails at decision time', function () {
    $swap = swapFixture($this->branchA);
    MemberUnavailability::create([
        'user_id' => $swap->claimed_by,
        'branch_id' => $this->branchA->id,
        'date' => $swap->assignment->event->starts_at->copy()->setTimezone(config('app.timezone'))->toDateString(),
        'part' => AvailabilityPart::WholeDay,
    ]);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(ListDutySwaps::class)
        ->callTableAction('approve', $swap)
        ->assertNotified(User::find($swap->claimed_by)->name.' marked this time as unavailable.');

    expect($swap->fresh()->status)->toBe(SwapStatus::Claimed);
});

it('forbids members and visitors', function () {
    $this->get(DutySwapResource::getUrl())->assertRedirect();

    $this->actingAs(User::factory()->member($this->branchA)->create());
    $this->get(DutySwapResource::getUrl())->assertForbidden();
});

it('shows the claimed live swap count as the nav badge, scoped to the branch', function () {
    swapFixture($this->branchA);
    swapFixture($this->branchA);
    swapFixture($this->branchA, SwapStatus::Open);
    swapFixture($this->branchA, SwapStatus::Claimed, past: true);
    swapFixture($this->branchB);

    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());
    expect(DutySwapResource::getNavigationBadge())->toBe('2');

    $this->actingAs(User::factory()->superAdmin()->create());
    expect(DutySwapResource::getNavigationBadge())->toBe('3');

    $this->actingAs(User::factory()->branchAdmin(Branch::factory()->create())->create());
    expect(DutySwapResource::getNavigationBadge())->toBeNull();
});
