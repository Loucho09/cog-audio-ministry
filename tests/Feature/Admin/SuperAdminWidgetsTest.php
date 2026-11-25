<?php

use App\Filament\Widgets\BranchOverviewWidget;
use App\Filament\Widgets\SiteSetupWidget;
use App\Models\Branch;
use App\Models\User;
use App\Support\SiteSetupChecklist;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    $this->a = Branch::factory()->create(['name' => 'Alpha', 'address' => 'Somewhere']);
    $this->b = Branch::factory()->create(['name' => 'Bravo', 'address' => null]);
});

function checklist(): array
{
    return collect(SiteSetupChecklist::items())->keyBy('label')->all();
}

it('restricts both widgets to super-admin', function (string $widget) {
    foreach (['branchAdmin', 'member'] as $role) {
        $this->actingAs(User::factory()->{$role}($this->a)->create());
        expect($widget::canView())->toBeFalse();
    }

    $this->actingAs(User::factory()->superAdmin()->create());
    expect($widget::canView())->toBeTrue();
})->with([BranchOverviewWidget::class, SiteSetupWidget::class]);

it('flags a branch without a head and shows the head of another', function () {
    User::factory()->branchAdmin($this->a)->create(['name' => 'Head Alpha']);
    User::factory()->branchAdmin($this->a)->create();
    User::factory()->member($this->a)->create();

    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(BranchOverviewWidget::class)
        ->assertCanSeeTableRecords([$this->a, $this->b])
        ->assertSee('Head Alpha')
        ->assertSee('No admin');
});

it('counts members and branch admins per branch', function () {
    User::factory()->branchAdmin($this->a)->create();
    User::factory()->branchAdmin($this->a)->create();
    User::factory()->member($this->a)->create();

    $this->actingAs(User::factory()->superAdmin()->create());

    $component = Livewire::test(BranchOverviewWidget::class);
    $record = $component->instance()->getTable()->getQuery()->find($this->a->id);

    expect($record->users_count)->toBe(3)
        ->and($record->admins_count)->toBe(2);
});

it('reports address missing or done from config', function () {
    config(['ministry.address' => null]);
    expect(checklist()['Church address']['done'])->toBeFalse();

    config(['ministry.address' => 'Marilag, Dasmariñas']);
    expect(checklist()['Church address']['done'])->toBeTrue();
});

it('lists branches without an address', function () {
    $item = checklist()['Branch addresses'];

    expect($item['done'])->toBeFalse()
        ->and($item['hint'])->toContain('Bravo')
        ->and($item['hint'])->not->toContain('Alpha');
});

it('renders the setup widget for super-admin', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(SiteSetupWidget::class)->assertSee('Site setup')->assertSee('Bravo');
});
