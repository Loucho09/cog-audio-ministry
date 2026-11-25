<?php

use App\Filament\Widgets\PendingApplicationsWidget;
use App\Models\Branch;
use App\Models\JoinApplication;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    foreach (['super-admin', 'branch-head', 'branch-admin', 'member'] as $role) {
        Role::findOrCreate($role, 'web');
    }
    $this->a = Branch::factory()->create();
    $this->b = Branch::factory()->create();
    $this->appA = JoinApplication::factory()->create(['branch_id' => $this->a->id]);
    $this->appB = JoinApplication::factory()->create(['branch_id' => $this->b->id]);
});

it('shows pending applications from all branches to a super-admin', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    expect(PendingApplicationsWidget::canView())->toBeTrue();
    Livewire::test(PendingApplicationsWidget::class)->assertCanSeeTableRecords([$this->appA, $this->appB]);
});

it('limits staff to their own branch', function (string $state) {
    $this->actingAs(User::factory()->{$state}($this->a)->create());

    expect(PendingApplicationsWidget::canView())->toBeTrue();
    Livewire::test(PendingApplicationsWidget::class)
        ->assertCanSeeTableRecords([$this->appA])
        ->assertCanNotSeeTableRecords([$this->appB]);
})->with(['branchHead', 'branchAdmin']);

it('hides approved and rejected applications', function () {
    $approved = JoinApplication::factory()->approved()->create(['branch_id' => $this->a->id]);
    $rejected = JoinApplication::factory()->rejected()->create(['branch_id' => $this->a->id]);
    $this->actingAs(User::factory()->branchHead($this->a)->create());

    Livewire::test(PendingApplicationsWidget::class)
        ->assertCanSeeTableRecords([$this->appA])
        ->assertCanNotSeeTableRecords([$approved, $rejected]);
});

it('lists at most 5 rows', function () {
    JoinApplication::factory()->count(6)->create(['branch_id' => $this->a->id]);
    $this->actingAs(User::factory()->branchHead($this->a)->create());

    $component = Livewire::test(PendingApplicationsWidget::class);

    expect($component->instance()->getTableRecords())->toHaveCount(5);
});

it('shows a friendly empty state', function () {
    JoinApplication::query()->delete();
    $this->actingAs(User::factory()->branchHead($this->a)->create());

    Livewire::test(PendingApplicationsWidget::class)->assertSee('No pending applications');
});

it('is not viewable by a member and they cannot reach the admin panel', function () {
    $this->actingAs(User::factory()->member($this->a)->create());

    expect(PendingApplicationsWidget::canView())->toBeFalse();
    $this->get('/admin')->assertForbidden();
});

it('is registered on the dashboard for a branch head and heads show the count', function () {
    $this->actingAs(User::factory()->branchHead($this->a)->create(['must_change_password' => false]));

    $this->get('/admin')->assertOk();
    expect(collect(Filament::getWidgets())->contains(PendingApplicationsWidget::class))->toBeTrue();
    Livewire::test(PendingApplicationsWidget::class)->assertSee('Pending applications (1)');
    Livewire::test(Dashboard::class)->assertSeeLivewire(PendingApplicationsWidget::class);
});
