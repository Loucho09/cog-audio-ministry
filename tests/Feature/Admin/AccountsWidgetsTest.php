<?php

use App\Filament\Widgets\AccountsAttentionWidget;
use App\Filament\Widgets\RecentAccountsWidget;
use App\Models\Branch;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    $this->a = Branch::factory()->create();
    $this->b = Branch::factory()->create();
});

it('restricts both widgets to admin roles', function (string $widget) {
    $this->actingAs(User::factory()->member($this->a)->create());
    expect($widget::canView())->toBeFalse();

    $this->actingAs(User::factory()->branchHead($this->a)->create());
    expect($widget::canView())->toBeTrue();
})->with([AccountsAttentionWidget::class, RecentAccountsWidget::class]);

it('shows own-branch temporary password accounts to a branch head only', function () {
    $mine = User::factory()->member($this->a)->create(['must_change_password' => true]);
    $other = User::factory()->member($this->b)->create(['must_change_password' => true]);
    $done = User::factory()->member($this->a)->create(['must_change_password' => false]);

    $this->actingAs(User::factory()->branchHead($this->a)->create());

    Livewire::test(AccountsAttentionWidget::class)
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$other, $done]);
});

it('shows no-branch accounts only to super-admin', function () {
    $orphan = User::factory()->create(['branch_id' => null, 'must_change_password' => false]);

    $this->actingAs(User::factory()->branchHead($this->a)->create());
    Livewire::test(AccountsAttentionWidget::class)->assertCanNotSeeTableRecords([$orphan]);

    $this->actingAs(User::factory()->superAdmin()->create(['must_change_password' => false]));
    Livewire::test(AccountsAttentionWidget::class)
        ->assertCanSeeTableRecords([$orphan])
        ->assertSee('No branch');
});

it('shows a friendly empty state', function () {
    $this->actingAs(User::factory()->branchHead($this->a)->create(['must_change_password' => false]));

    Livewire::test(AccountsAttentionWidget::class)->assertSee('All accounts are set up');
});

it('limits recent accounts to 5 in scope', function () {
    $mine = User::factory()->member($this->a)->count(6)->create();
    $other = User::factory()->member($this->b)->create();

    $this->actingAs(User::factory()->branchHead($this->a)->create());

    $component = Livewire::test(RecentAccountsWidget::class);
    $component->assertCanNotSeeTableRecords([$other]);

    expect($component->instance()->getTableRecords())->toHaveCount(5);
});
