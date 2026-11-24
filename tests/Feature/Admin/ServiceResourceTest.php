<?php

use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Models\Branch;
use App\Models\Service;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    $this->branchA = Branch::factory()->create();
    $this->branchB = Branch::factory()->create();
    $this->serviceA = Service::factory()->create(['branch_id' => $this->branchA->id, 'name' => 'Alpha Service']);
    $this->serviceB = Service::factory()->create(['branch_id' => $this->branchB->id, 'name' => 'Bravo Service']);
});

it('lets a super-admin see every branch', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(ListServices::class)
        ->assertCanSeeTableRecords([$this->serviceA, $this->serviceB]);
});

it('limits a branch-head to their own branch', function () {
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    Livewire::test(ListServices::class)
        ->assertCanSeeTableRecords([$this->serviceA])
        ->assertCanNotSeeTableRecords([$this->serviceB]);
});

it('hides another branch edit page from a branch-head', function () {
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    $this->get("/admin/services/{$this->serviceB->id}/edit")->assertNotFound();
});

it('forces a branch-head new service into their own branch', function () {
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    Livewire::test(CreateService::class)
        ->fillForm(['name' => 'New', 'day_of_week' => 3, 'start_time' => '19:00', 'branch_id' => $this->branchB->id])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Service::where('name', 'New')->firstOrFail()->branch_id)->toBe($this->branchA->id);
});

it('forces branch on update for a branch-head', function () {
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    Livewire::test(EditService::class, ['record' => $this->serviceA->id])
        ->fillForm(['name' => 'Renamed', 'branch_id' => $this->branchB->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->serviceA->fresh()->branch_id)->toBe($this->branchA->id);
});

it('rejects a duplicate branch/day/time slot', function () {
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    Livewire::test(CreateService::class)
        ->fillForm(['name' => 'Dup', 'day_of_week' => 0, 'start_time' => '09:00'])
        ->call('create')
        ->assertHasFormErrors(['start_time']);

    expect(Service::where('name', 'Dup')->exists())->toBeFalse();
});

it('allows saving an existing service without tripping the slot rule', function () {
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    Livewire::test(EditService::class, ['record' => $this->serviceA->id])
        ->fillForm(['name' => 'Same slot'])
        ->call('save')
        ->assertHasNoFormErrors();
});

it('rejects ends_on before starts_on', function () {
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    Livewire::test(CreateService::class)
        ->fillForm([
            'name' => 'Window', 'day_of_week' => 2, 'start_time' => '10:00',
            'starts_on' => '2026-12-10', 'ends_on' => '2026-12-01',
        ])
        ->call('create')
        ->assertHasFormErrors(['ends_on']);
});

it('forbids members from the services page', function () {
    $this->actingAs(User::factory()->member($this->branchA)->create());

    $this->get('/admin/services')->assertForbidden();
});
