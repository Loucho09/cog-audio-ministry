<?php

use App\Filament\Resources\GalleryImages\GalleryImageResource;
use App\Filament\Resources\Services\ServiceResource;
use App\Filament\Resources\TeamMembers\TeamMemberResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\BranchStatsWidget;
use App\Models\Branch;
use App\Models\GalleryImage;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    $this->a = Branch::factory()->create();
    $this->b = Branch::factory()->create();
    foreach ([1, 2] as $day) {
        Service::factory()->create(['branch_id' => $this->a->id, 'day_of_week' => $day]);
    }
    foreach ([1, 2, 3] as $day) {
        Service::factory()->create(['branch_id' => $this->b->id, 'day_of_week' => $day]);
    }
    GalleryImage::factory()->count(2)->create(['branch_id' => $this->b->id]);
    TeamMember::factory()->count(2)->create(['branch_id' => $this->b->id]);
    User::factory()->member($this->b)->count(2)->create();
});

function statValues(): array
{
    $stats = invade(Livewire::test(BranchStatsWidget::class)->instance())->getStats();

    return collect($stats)->mapWithKeys(fn ($s) => [(string) $s->getLabel() => $s->getValue()])->all();
}

it('limits a branch-head to their own branch counts', function () {
    $this->actingAs(User::factory()->branchHead($this->a)->create());

    $values = statValues();

    expect($values['Services'])->toBe(2)
        ->and($values['Members'])->toBe(1)
        ->and($values['Gallery photos'])->toBe(0)
        ->and($values['Team members'])->toBe(0);
});

it('shows zero counts to a branch-head without a branch', function () {
    $user = User::factory()->create(['branch_id' => null]);
    Role::findOrCreate('branch-head', 'web');
    $user->assignRole('branch-head');
    $this->actingAs($user);

    expect(BranchStatsWidget::canView())->toBeFalse()
        ->and(UserResource::getEloquentQuery()->count())->toBe(0)
        ->and(ServiceResource::getEloquentQuery()->count())->toBe(0)
        ->and(GalleryImageResource::getEloquentQuery()->count())->toBe(0)
        ->and(TeamMemberResource::getEloquentQuery()->count())->toBe(0);
});

it('shows super-admin totals across branches', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $values = statValues();

    expect($values['Services'])->toBe(5)
        ->and($values['Members'])->toBe(2 + 1)
        ->and($values['Gallery photos'])->toBe(2)
        ->and($values['Team members'])->toBe(2);
});

it('only lets admins view the widget', function () {
    $this->actingAs(User::factory()->member($this->a)->create());
    expect(BranchStatsWidget::canView())->toBeFalse();

    $this->actingAs(User::factory()->branchAdmin($this->a)->create());
    expect(BranchStatsWidget::canView())->toBeTrue();
});
