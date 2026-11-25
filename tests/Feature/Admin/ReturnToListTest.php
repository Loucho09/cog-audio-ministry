<?php

use App\Filament\Resources\DutyRoles\DutyRoleResource;
use App\Filament\Resources\DutyRoles\Pages\CreateDutyRole;
use App\Filament\Resources\DutyRoles\Pages\EditDutyRole;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\ServiceResource;
use App\Models\Branch;
use App\Models\DutyRole;
use App\Models\Event;
use App\Models\Service;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

// Livewire::test sends no Referer header, so tests set the session previous URL,
// which is what url()->previous() falls back to.

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    $this->branch = Branch::factory()->create();
    $this->actingAs(User::factory()->branchAdmin($this->branch)->create());
});

it('returns to the list after creating a duty role', function () {
    Livewire::test(CreateDutyRole::class)
        ->fillForm(['name' => 'Lights', 'sort_order' => 1])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(DutyRoleResource::getUrl('index'));
});

it('returns to the list after editing a duty role', function () {
    $role = DutyRole::factory()->create(['branch_id' => $this->branch->id]);

    Livewire::test(EditDutyRole::class, ['record' => $role->getRouteKey()])
        ->fillForm(['name' => 'Renamed'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertRedirect(DutyRoleResource::getUrl('index'));
});

it('returns to the list after editing a service', function () {
    $service = Service::factory()->create(['branch_id' => $this->branch->id]);

    Livewire::test(EditService::class, ['record' => $service->getRouteKey()])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertRedirect(ServiceResource::getUrl('index'));
});

it('keeps the list query string the edit page was opened from', function () {
    $role = DutyRole::factory()->create(['branch_id' => $this->branch->id]);
    $list = DutyRoleResource::getUrl('index').'?page=2&search=snd';
    session()->setPreviousUrl($list);

    Livewire::test(EditDutyRole::class, ['record' => $role->getRouteKey()])
        ->fillForm(['name' => 'Kept'])
        ->call('save')
        ->assertRedirect($list);
});

it('ignores a foreign referer and falls back to the plain index', function () {
    $role = DutyRole::factory()->create(['branch_id' => $this->branch->id]);
    session()->setPreviousUrl('https://evil.example/admin/duty-roles?page=2');

    Livewire::test(EditDutyRole::class, ['record' => $role->getRouteKey()])
        ->fillForm(['name' => 'Safe'])
        ->call('save')
        ->assertRedirect(DutyRoleResource::getUrl('index'));
});

it('goes to the event page after creating an event', function () {
    $role = DutyRole::factory()->create(['branch_id' => $this->branch->id]);
    $start = now()->addWeeks(2)->setTime(9, 0);

    $component = Livewire::test(CreateEvent::class)
        ->fillForm([
            'name' => 'Special Night',
            'starts_at' => $start->format('Y-m-d H:i:s'),
            'ends_at' => $start->copy()->addHours(2)->format('Y-m-d H:i:s'),
            'dutySlots' => [['duty_role_id' => $role->id, 'quantity' => 1]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $event = Event::where('name', 'Special Night')->firstOrFail();

    $component->assertRedirect(EventResource::getUrl('view', ['record' => $event]));
});
