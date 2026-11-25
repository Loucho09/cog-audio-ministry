<?php

use App\Enums\DutyStatus;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\Pages\ViewEvent;
use App\Filament\Resources\Events\RelationManagers\AssignmentsRelationManager;
use App\Models\Branch;
use App\Models\DutyAssignment;
use App\Models\DutyRole;
use App\Models\Event;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    $this->branchA = Branch::factory()->create();
    $this->branchB = Branch::factory()->create();
    $this->role = DutyRole::factory()->create(['branch_id' => $this->branchA->id, 'name' => 'Sound']);
    $this->otherRole = DutyRole::factory()->create(['branch_id' => $this->branchA->id, 'name' => 'Lights']);
    $this->event = Event::factory()->create(['branch_id' => $this->branchA->id]);
    $this->event->dutySlots()->create(['duty_role_id' => $this->role->id, 'quantity' => 1]);
    $this->member = User::factory()->member($this->branchA)->create();
});

function manager(Event $event)
{
    return Livewire::test(AssignmentsRelationManager::class, ['ownerRecord' => $event, 'pageClass' => EditEvent::class]);
}

function makeAssignment($test, ?User $user = null, ?Event $event = null): DutyAssignment
{
    $event ??= $test->event;

    return DutyAssignment::factory()->create([
        'event_id' => $event->id,
        'branch_id' => $event->branch_id,
        'duty_role_id' => $test->role->id,
        'user_id' => ($user ?? User::factory()->member($test->branchA)->create())->id,
    ]);
}

it('assigns a member to a slot', function () {
    $admin = User::factory()->branchAdmin($this->branchA)->create();
    $this->actingAs($admin);

    manager($this->event)
        ->callTableAction('assign', data: ['duty_role_id' => $this->role->id, 'user_id' => $this->member->id])
        ->assertHasNoFormErrors()
        ->assertNotified();

    $a = DutyAssignment::firstOrFail();
    expect($a->user_id)->toBe($this->member->id)->and($a->assigned_by)->toBe($admin->id);
});

it('shows a danger notification and creates nothing when over capacity', function () {
    makeAssignment($this);
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    manager($this->event)
        ->callTableAction('assign', data: ['duty_role_id' => $this->role->id, 'user_id' => $this->member->id])
        ->assertNotified('That duty is already full for this event.');

    expect(DutyAssignment::count())->toBe(1);
});

it('removes an assignment and frees the slot', function () {
    $a = makeAssignment($this);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    manager($this->event)->callTableAction('remove', $a)->assertNotified();

    expect(DutyAssignment::count())->toBe(0);
    manager($this->event)
        ->callTableAction('assign', data: ['duty_role_id' => $this->role->id, 'user_id' => $this->member->id]);
    expect(DutyAssignment::count())->toBe(1);
});

it('hides remove for declined assignments', function () {
    $a = makeAssignment($this);
    $a->update(['status' => DutyStatus::Declined]);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    manager($this->event)->assertTableActionHidden('remove', $a);
});

it('hides assign and remove on a started event even for super-admin', function () {
    $past = Event::factory()->create([
        'branch_id' => $this->branchA->id,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->subDay()->addHours(2),
    ]);
    $past->dutySlots()->create(['duty_role_id' => $this->role->id, 'quantity' => 1]);
    $a = makeAssignment($this, null, $past);
    $this->actingAs(User::factory()->superAdmin()->create());

    manager($past)->assertTableActionHidden('assign')->assertTableActionHidden('remove', $a);
});

it('rejects a member from another branch', function () {
    $outsider = User::factory()->member($this->branchB)->create();
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    manager($this->event)
        ->callTableAction('assign', data: ['duty_role_id' => $this->role->id, 'user_id' => $outsider->id])
        ->assertHasFormErrors(['user_id']);

    expect(DutyAssignment::count())->toBe(0);
});

it('rejects a role without a slot on the event', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    manager($this->event)
        ->callTableAction('assign', data: ['duty_role_id' => $this->otherRole->id, 'user_id' => $this->member->id])
        ->assertHasFormErrors(['duty_role_id']);

    expect(DutyAssignment::count())->toBe(0);
});

it('shows the slot summary of active assignments only and only this event rows', function () {
    $declined = makeAssignment($this);
    $declined->update(['status' => DutyStatus::Declined]);
    $other = Event::factory()->create(['branch_id' => $this->branchA->id]);
    $foreign = makeAssignment($this, null, $other);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    manager($this->event)
        ->assertSeeText('Sound: 0 of 1')
        ->assertCanSeeTableRecords([$declined])
        ->assertCanNotSeeTableRecords([$foreign]);
});

it('is not reachable for another branch event', function () {
    $eventB = Event::factory()->create(['branch_id' => $this->branchB->id]);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    $this->get(EventResource::getUrl('edit', ['record' => $eventB]))->assertNotFound();
});

it('lets a branch-admin view a started own-branch event and see its assignments', function () {
    $past = Event::factory()->create([
        'branch_id' => $this->branchA->id,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->subDay()->addHours(2),
    ]);
    $past->dutySlots()->create(['duty_role_id' => $this->role->id, 'quantity' => 1]);
    $a = makeAssignment($this, $this->member, $past);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    $this->get(EventResource::getUrl('edit', ['record' => $past]))->assertForbidden();
    $this->get(EventResource::getUrl('view', ['record' => $past]))->assertOk();

    Livewire::test(AssignmentsRelationManager::class, ['ownerRecord' => $past, 'pageClass' => ViewEvent::class])
        ->assertCanSeeTableRecords([$a])
        ->assertTableActionHidden('assign');
});

it('does not show another branch event view page', function () {
    $foreign = Event::factory()->create([
        'branch_id' => $this->branchB->id,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->subDay()->addHours(2),
    ]);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    $this->get(EventResource::getUrl('view', ['record' => $foreign]))->assertNotFound();
});

it('cannot mount the assignments manager for another branch event', function () {
    $foreign = Event::factory()->create(['branch_id' => $this->branchB->id]);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    manager($foreign)->assertForbidden();
});

it('cannot assign or remove a guest on another branch event', function () {
    $foreign = Event::factory()->create(['branch_id' => $this->branchB->id]);
    $foreign->dutySlots()->create(['duty_role_id' => $this->role->id, 'quantity' => 1]);
    $g = DutyAssignment::factory()->guest()->create([
        'event_id' => $foreign->id, 'branch_id' => $this->branchB->id, 'duty_role_id' => $this->role->id,
    ]);

    foreach ([User::factory()->branchAdmin($this->branchA)->create(), User::factory()->branchHead($this->branchA)->create()] as $admin) {
        $this->actingAs($admin);

        manager($foreign)->assertForbidden();
    }

    expect(DutyAssignment::count())->toBe(1)->and(DutyAssignment::find($g->id))->not->toBeNull();
});

it('rejects a tampered assignee type', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    manager($this->event)
        ->callTableAction('assign', data: ['assignee_type' => 'robot', 'duty_role_id' => $this->role->id, 'guest_name' => 'Pedro'])
        ->assertHasFormErrors(['assignee_type']);

    expect(DutyAssignment::count())->toBe(0);
});

it('assigns a typed guest name', function () {
    $admin = User::factory()->branchAdmin($this->branchA)->create();
    $this->actingAs($admin);

    manager($this->event)
        ->callTableAction('assign', data: ['assignee_type' => 'guest', 'duty_role_id' => $this->role->id, 'guest_name' => 'Juan Dela Cruz'])
        ->assertHasNoFormErrors()
        ->assertNotified('Guest assigned');

    $a = DutyAssignment::firstOrFail();
    expect($a->user_id)->toBeNull()
        ->and($a->guest_name)->toBe('Juan Dela Cruz')
        ->and($a->status)->toBe(DutyStatus::Accepted)
        ->and($a->assigned_by)->toBe($admin->id);

    manager($this->event)->assertSeeText('Juan Dela Cruz (Guest)')->assertSeeText('Confirmed');
});

it('ignores user_id in guest mode and guest_name in member mode', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    manager($this->event)->callTableAction('assign', data: [
        'assignee_type' => 'guest', 'duty_role_id' => $this->role->id, 'guest_name' => 'Maria', 'user_id' => $this->member->id,
    ]);
    expect(DutyAssignment::firstOrFail()->user_id)->toBeNull();

    DutyAssignment::query()->delete();

    manager($this->event)->callTableAction('assign', data: [
        'assignee_type' => 'member', 'duty_role_id' => $this->role->id, 'user_id' => $this->member->id, 'guest_name' => 'Ignored',
    ]);
    $a = DutyAssignment::firstOrFail();
    expect($a->user_id)->toBe($this->member->id)->and($a->guest_name)->toBeNull();
});

it('requires a guest name in guest mode', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    manager($this->event)
        ->callTableAction('assign', data: ['assignee_type' => 'guest', 'duty_role_id' => $this->role->id, 'guest_name' => ''])
        ->assertHasFormErrors(['guest_name']);

    expect(DutyAssignment::count())->toBe(0);
});

it('shows a danger notification and creates no guest row when the slot is full', function () {
    makeAssignment($this);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    manager($this->event)
        ->callTableAction('assign', data: ['assignee_type' => 'guest', 'duty_role_id' => $this->role->id, 'guest_name' => 'Pedro'])
        ->assertNotified('That duty is already full for this event.');

    expect(DutyAssignment::count())->toBe(1);
});

it('removes a guest assignment', function () {
    $g = DutyAssignment::factory()->guest()->create([
        'event_id' => $this->event->id, 'branch_id' => $this->branchA->id, 'duty_role_id' => $this->role->id,
    ]);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    manager($this->event)->callTableAction('remove', $g)->assertNotified();

    expect(DutyAssignment::count())->toBe(0);
});
