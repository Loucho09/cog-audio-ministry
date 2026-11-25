<?php

use App\Filament\Resources\DutyRoles\DutyRoleResource;
use App\Filament\Resources\DutyRoles\Pages\CreateDutyRole;
use App\Filament\Resources\DutyRoles\Pages\EditDutyRole;
use App\Filament\Resources\DutyRoles\Pages\ListDutyRoles;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Models\Branch;
use App\Models\DutyRole;
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
});

it('lists only own-branch roles', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(ListDutyRoles::class)
        ->assertCanSeeTableRecords([$this->roleA])
        ->assertCanNotSeeTableRecords([$this->roleB]);
});

it('lets a branch-admin create a role in own branch', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(CreateDutyRole::class)
        ->fillForm(['name' => 'Lights', 'sort_order' => 1, 'branch_id' => $this->branchB->id])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(DutyRole::where('name', 'Lights')->firstOrFail()->branch_id)->toBe($this->branchA->id);
});

it('hides another branch role edit page', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    $this->get(DutyRoleResource::getUrl('edit', ['record' => $this->roleB]))->assertNotFound();
});

it('edits an own-branch role', function () {
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    Livewire::test(EditDutyRole::class, ['record' => $this->roleA->id])
        ->fillForm(['name' => 'Renamed'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->roleA->fresh()->name)->toBe('Renamed');
});

it('refuses a duplicate role name in the same branch with a form error', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(CreateDutyRole::class)
        ->fillForm(['name' => 'Sound A'])
        ->call('create')
        ->assertHasFormErrors(['name']);
});

it('allows the same role name in another branch', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(CreateDutyRole::class)
        ->fillForm(['name' => 'Sound B'])
        ->call('create')
        ->assertHasNoFormErrors();
});

it('allows saving a role without tripping its own name', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(EditDutyRole::class, ['record' => $this->roleA->id])
        ->fillForm(['sort_order' => 5])
        ->call('save')
        ->assertHasNoFormErrors();
});

it('forbids members and branchless admins', function () {
    $this->actingAs(User::factory()->member($this->branchA)->create());
    $this->get('/admin/duty-roles')->assertForbidden();

    $admin = User::factory()->branchAdmin($this->branchA)->create();
    $admin->forceFill(['branch_id' => null])->save();
    $this->actingAs($admin->fresh());
    $this->get('/admin/duty-roles')->assertForbidden();
});

it('puts duty roles in the Roster navigation group', function () {
    expect(DutyRoleResource::getNavigationGroup())->toBe('Roster');
});

it('saves default slots on a service form', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(CreateService::class)
        ->fillForm([
            'name' => 'Slots', 'day_of_week' => 3, 'start_time' => '19:00',
            'dutySlots' => [['duty_role_id' => $this->roleA->id, 'quantity' => 2]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $slot = Service::where('name', 'Slots')->firstOrFail()->dutySlots()->firstOrFail();
    expect($slot->duty_role_id)->toBe($this->roleA->id)->and($slot->quantity)->toBe(2);
});

it('refuses out of range quantities', function (int $qty) {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(CreateService::class)
        ->fillForm([
            'name' => 'Bad', 'day_of_week' => 3, 'start_time' => '19:00',
            'dutySlots' => [['duty_role_id' => $this->roleA->id, 'quantity' => $qty]],
        ])
        ->call('create')
        ->assertHasFormErrors();

    expect(Service::where('name', 'Bad')->exists())->toBeFalse();
})->with([0, 21]);

it('refuses a role id from another branch', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(CreateService::class)
        ->fillForm([
            'name' => 'Cross', 'day_of_week' => 3, 'start_time' => '19:00',
            'dutySlots' => [['duty_role_id' => $this->roleB->id, 'quantity' => 1]],
        ])
        ->call('create')
        ->assertHasFormErrors();

    expect(Service::where('name', 'Cross')->exists())->toBeFalse();
});

it('refuses an inactive role', function () {
    $this->roleA->update(['is_active' => false]);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(CreateService::class)
        ->fillForm([
            'name' => 'Inactive', 'day_of_week' => 3, 'start_time' => '19:00',
            'dutySlots' => [['duty_role_id' => $this->roleA->id, 'quantity' => 1]],
        ])
        ->call('create')
        ->assertHasFormErrors();
});

it('refuses duplicate roles in one repeater', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(CreateService::class)
        ->fillForm([
            'name' => 'Dupes', 'day_of_week' => 3, 'start_time' => '19:00',
            'dutySlots' => [
                ['duty_role_id' => $this->roleA->id, 'quantity' => 1],
                ['duty_role_id' => $this->roleA->id, 'quantity' => 2],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors();
});

it('lets a branch-admin open and save the edit page for an own service', function () {
    $service = Service::factory()->create(['branch_id' => $this->branchA->id]);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(EditService::class, ['record' => $service->id])
        ->assertSuccessful()
        ->fillForm(['dutySlots' => [['duty_role_id' => $this->roleA->id, 'quantity' => 3]]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($service->dutySlots()->first()->quantity)->toBe(3);
});

it('hides another branch service edit page from a branch-admin', function () {
    $service = Service::factory()->create(['branch_id' => $this->branchB->id]);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    $this->get("/admin/services/{$service->id}/edit")->assertNotFound();
});

it('denies a branch-admin with no branch on services', function () {
    $admin = User::factory()->branchAdmin($this->branchA)->create();
    $admin->forceFill(['branch_id' => null])->save();
    $this->actingAs($admin->fresh());

    $this->get('/admin/services')->assertForbidden();
    $this->get('/admin/services/create')->assertForbidden();
});

it('keeps a service slot with a deactivated role editable', function () {
    $service = Service::factory()->create(['branch_id' => $this->branchA->id]);
    $service->dutySlots()->create(['duty_role_id' => $this->roleA->id, 'quantity' => 1]);
    $this->roleA->update(['is_active' => false]);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(EditService::class, ['record' => $service->id])
        ->fillForm(['name' => 'Renamed service'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($service->fresh()->name)->toBe('Renamed service')
        ->and($service->dutySlots()->count())->toBe(1);
});

it('refuses a deactivated role as a new service slot', function () {
    $service = Service::factory()->create(['branch_id' => $this->branchA->id]);
    $this->roleA->update(['is_active' => false]);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(EditService::class, ['record' => $service->id])
        ->fillForm(['dutySlots' => [['duty_role_id' => $this->roleA->id, 'quantity' => 1]]])
        ->call('save')
        ->assertHasFormErrors(['dutySlots.0.duty_role_id']);
});
