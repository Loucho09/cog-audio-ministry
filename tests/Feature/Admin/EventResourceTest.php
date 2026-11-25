<?php

use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Models\Branch;
use App\Models\DutyAssignment;
use App\Models\DutyRole;
use App\Models\Event;
use App\Models\Service;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    $this->branchA = Branch::factory()->create();
    $this->branchB = Branch::factory()->create();
    $this->roleA = DutyRole::factory()->create(['branch_id' => $this->branchA->id, 'name' => 'Sound A']);
    $this->roleB = DutyRole::factory()->create(['branch_id' => $this->branchB->id, 'name' => 'Sound B']);
    $this->eventA = Event::factory()->create(['branch_id' => $this->branchA->id]);
    $this->eventB = Event::factory()->create(['branch_id' => $this->branchB->id]);
});

function eventForm(array $overrides = []): array
{
    $start = now()->addWeeks(2)->setTime(9, 0);

    return array_merge([
        'name' => 'Special Night',
        'starts_at' => $start->format('Y-m-d H:i:s'),
        'ends_at' => $start->copy()->addHours(2)->format('Y-m-d H:i:s'),
    ], $overrides);
}

it('lists only own-branch events for a branch-admin', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(ListEvents::class)
        ->assertCanSeeTableRecords([$this->eventA])
        ->assertCanNotSeeTableRecords([$this->eventB]);
});

it('returns not found for another branch event edit url', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    $this->get(EventResource::getUrl('edit', ['record' => $this->eventB]))->assertNotFound();
});

it('forbids members', function () {
    $this->actingAs(User::factory()->member($this->branchA)->create());

    $this->get('/admin/events')->assertForbidden();
});

it('creates an event with slots in own branch ignoring a posted branch', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(CreateEvent::class)
        ->fillForm(eventForm([
            'branch_id' => $this->branchB->id,
            'dutySlots' => [['duty_role_id' => $this->roleA->id, 'quantity' => 2]],
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    $event = Event::where('name', 'Special Night')->firstOrFail();
    expect($event->branch_id)->toBe($this->branchA->id)
        ->and($event->dutySlots()->firstOrFail()->quantity)->toBe(2);
});

it('requires ends_at after starts_at', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());
    $start = now()->addWeeks(2)->setTime(9, 0);

    Livewire::test(CreateEvent::class)
        ->fillForm(eventForm([
            'starts_at' => $start->format('Y-m-d H:i:s'),
            'ends_at' => $start->copy()->subHour()->format('Y-m-d H:i:s'),
        ]))
        ->call('create')
        ->assertHasFormErrors(['ends_at']);
});

it('refuses a role from another branch and out of range quantities', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(CreateEvent::class)
        ->fillForm(eventForm(['dutySlots' => [['duty_role_id' => $this->roleB->id, 'quantity' => 1]]]))
        ->call('create')
        ->assertHasFormErrors(['dutySlots.0.duty_role_id']);

    Livewire::test(CreateEvent::class)
        ->fillForm(eventForm(['dutySlots' => [['duty_role_id' => $this->roleA->id, 'quantity' => 21]]]))
        ->call('create')
        ->assertHasFormErrors(['dutySlots.0.quantity']);

    expect(Event::where('name', 'Special Night')->exists())->toBeFalse();
});

it('refuses a service from another branch', function () {
    $service = Service::factory()->create(['branch_id' => $this->branchB->id]);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(CreateEvent::class)
        ->fillForm(eventForm(['service_id' => $service->id]))
        ->call('create')
        ->assertHasFormErrors(['service_id']);
});

it('edits an own event and its slots through the service', function () {
    $this->eventA->dutySlots()->create(['duty_role_id' => $this->roleA->id, 'quantity' => 1]);
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    Livewire::test(EditEvent::class, ['record' => $this->eventA->id])
        ->fillForm([
            'name' => 'Renamed',
            'dutySlots' => [['duty_role_id' => $this->roleA->id, 'quantity' => 4]],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->eventA->fresh()->name)->toBe('Renamed')
        ->and($this->eventA->dutySlots()->first()->quantity)->toBe(4);
});

it('shows an error and saves nothing when lowering a filled slot', function () {
    $this->eventA->dutySlots()->create(['duty_role_id' => $this->roleA->id, 'quantity' => 2]);
    DutyAssignment::factory()->count(2)->create([
        'event_id' => $this->eventA->id,
        'branch_id' => $this->branchA->id,
        'duty_role_id' => $this->roleA->id,
        'user_id' => fn () => User::factory()->member($this->branchA)->create()->id,
    ]);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(EditEvent::class, ['record' => $this->eventA->id])
        ->fillForm([
            'name' => 'Should not save',
            'dutySlots' => [['duty_role_id' => $this->roleA->id, 'quantity' => 1]],
        ])
        ->call('save')
        ->assertNotified();

    expect($this->eventA->fresh()->name)->not->toBe('Should not save')
        ->and($this->eventA->dutySlots()->first()->quantity)->toBe(2);
});

it('has no edit or delete action for a started event, even for super-admin', function () {
    $past = Event::factory()->create([
        'branch_id' => $this->branchA->id,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->subDay()->addHours(2),
    ]);

    foreach ([User::factory()->branchAdmin($this->branchA)->create(), User::factory()->superAdmin()->create()] as $user) {
        $this->actingAs($user);

        Livewire::test(ListEvents::class)
            ->filterTable('timing', 'past')
            ->assertTableActionHidden('delete', $past)
            ->assertTableActionHidden('edit', $past);
    }
});

it('refuses to save a started event even for super-admin', function () {
    $past = Event::factory()->create([
        'branch_id' => $this->branchA->id,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->subDay()->addHours(2),
    ]);
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(EditEvent::class, ['record' => $past->id])
        ->fillForm(['name' => 'Hacked'])
        ->call('save');

    expect($past->fresh()->name)->not->toBe('Hacked');
});

it('shows filled and needed counts of active assignments only', function () {
    $this->eventA->dutySlots()->create(['duty_role_id' => $this->roleA->id, 'quantity' => 3]);
    DutyAssignment::factory()->create([
        'event_id' => $this->eventA->id,
        'branch_id' => $this->branchA->id,
        'duty_role_id' => $this->roleA->id,
        'user_id' => User::factory()->member($this->branchA)->create()->id,
    ]);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(ListEvents::class)->assertSeeText('1 / 3');
});

it('generates events for a branch-admin own branch', function () {
    $service = Service::factory()->create(['branch_id' => $this->branchA->id, 'day_of_week' => 0]);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());
    $from = now()->startOfWeek()->addWeek();

    Livewire::test(ListEvents::class)
        ->callAction('generate', ['from' => $from->format('Y-m-d'), 'to' => $from->copy()->addDays(20)->format('Y-m-d')])
        ->assertHasNoFormErrors()
        ->assertNotified('3 events created');

    expect(Event::where('service_id', $service->id)->count())->toBe(3);
});

it('lets super-admin generate for a chosen branch', function () {
    Service::factory()->create(['branch_id' => $this->branchB->id, 'day_of_week' => 0]);
    $this->actingAs(User::factory()->superAdmin()->create());
    $from = now()->startOfDay();

    Livewire::test(ListEvents::class)
        ->callAction('generate', [
            'branch_id' => $this->branchB->id,
            'from' => $from->format('Y-m-d'),
            'to' => $from->copy()->addWeeks(2)->format('Y-m-d'),
        ])
        ->assertHasNoFormErrors();

    expect(Event::where('branch_id', $this->branchB->id)->whereNotNull('service_id')->exists())->toBeTrue();
});

it('shows a danger notification for a range over 3 months', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());
    $from = now()->startOfDay();

    Livewire::test(ListEvents::class)
        ->callAction('generate', ['from' => $from->format('Y-m-d'), 'to' => $from->copy()->addMonths(5)->format('Y-m-d')])
        ->assertNotified();

    expect(Event::where('branch_id', $this->branchA->id)->count())->toBe(1);
});
