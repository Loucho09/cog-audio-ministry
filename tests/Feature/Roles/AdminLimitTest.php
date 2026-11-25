<?php

use App\Actions\AssignRole;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Branch;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->trece = Branch::factory()->create(['name' => 'COG Trece', 'slug' => 'trece']);
    $this->dasma = Branch::factory()->create(['name' => 'COG Dasmariñas', 'slug' => 'dasma', 'is_main' => true]);
    $this->owner = User::factory()->superAdmin()->create();
});

it('blocks a second branch-admin in a branch with limit 1', function () {
    User::factory()->branchAdmin($this->trece)->create();
    $member = User::factory()->member($this->trece)->create();

    expect(fn () => app(AssignRole::class)->handle($this->owner, $member, 'branch-admin'))
        ->toThrow(ValidationException::class, 'COG Trece already has its 1 branch admin');

    expect($member->fresh()->hasRole('member'))->toBeTrue();
});

it('allows two branch-admins in Dasma and blocks the third', function () {
    User::factory()->branchAdmin($this->dasma)->create();
    $second = User::factory()->member($this->dasma)->create();
    $third = User::factory()->member($this->dasma)->create();

    app(AssignRole::class)->handle($this->owner, $second, 'branch-admin');
    expect($second->fresh()->hasRole('branch-admin'))->toBeTrue();

    expect(fn () => app(AssignRole::class)->handle($this->owner, $third, 'branch-admin'))
        ->toThrow(ValidationException::class);
});

it('lets an existing admin be re-saved with the same role', function () {
    $admin = User::factory()->branchAdmin($this->trece)->create();

    app(AssignRole::class)->handle($this->owner, $admin, 'branch-admin');

    expect($admin->fresh()->hasRole('branch-admin'))->toBeTrue();
});

it('blocks moving a branch-admin into a full branch', function () {
    Filament::setCurrentPanel('admin');
    User::factory()->branchAdmin($this->trece)->create();
    $mover = User::factory()->branchAdmin($this->dasma)->create();
    $this->actingAs($this->owner);

    Livewire::test(EditUser::class, ['record' => $mover->id])
        ->fillForm(['branch_id' => $this->trece->id])
        ->call('save')
        ->assertHasFormErrors(['branch_id']);

    expect($mover->fresh()->branch_id)->toBe($this->dasma->id);
});

it('lets a branch-admin move into a branch with a free slot', function () {
    Filament::setCurrentPanel('admin');
    $mover = User::factory()->branchAdmin($this->dasma)->create();
    $this->actingAs($this->owner);

    Livewire::test(EditUser::class, ['record' => $mover->id])
        ->fillForm(['branch_id' => $this->trece->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($mover->fresh()->branch_id)->toBe($this->trece->id);
});

it('reads the limit from config so it can change without code', function () {
    config(['ministry.branch_admin_limit' => ['default' => 3, 'branches' => []]]);

    User::factory()->branchAdmin($this->trece)->count(2)->create();
    $member = User::factory()->member($this->trece)->create();

    app(AssignRole::class)->handle($this->owner, $member, 'branch-admin');

    expect($member->fresh()->hasRole('branch-admin'))->toBeTrue();
});

it('has no branch-head role any more', function () {
    expect(AssignRole::ROLES)->not->toContain('branch-head');
});
