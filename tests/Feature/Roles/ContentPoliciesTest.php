<?php

use App\Models\Branch;
use App\Models\GalleryImage;
use App\Models\TeamMember;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->branch = Branch::factory()->create();
    $this->other = Branch::factory()->create();
});

dataset('branchContent', [GalleryImage::class, TeamMember::class]);

it('lets super-admin do everything on branch content', function (string $model) {
    $user = User::factory()->superAdmin()->create();
    $record = $model::factory()->create(['branch_id' => null]);

    expect($user->can('create', $model))->toBeTrue()
        ->and($user->can('update', $record))->toBeTrue()
        ->and($user->can('delete', $record))->toBeTrue();
})->with('branchContent');

it('lets branch-head manage own-branch content only', function (string $model) {
    $head = User::factory()->branchHead($this->branch)->create();
    $own = $model::factory()->create(['branch_id' => $this->branch->id]);
    $foreign = $model::factory()->create(['branch_id' => $this->other->id]);
    $global = $model::factory()->create(['branch_id' => null]);

    expect($head->can('viewAny', $model))->toBeTrue()
        ->and($head->can('create', $model))->toBeTrue()
        ->and($head->can('update', $own))->toBeTrue()
        ->and($head->can('delete', $own))->toBeTrue()
        ->and($head->can('view', $global))->toBeTrue()
        ->and($head->can('view', $foreign))->toBeFalse()
        ->and($head->can('update', $foreign))->toBeFalse()
        ->and($head->can('update', $global))->toBeFalse()
        ->and($head->can('delete', $global))->toBeFalse()
        ->and($head->can('restore', $own))->toBeFalse()
        ->and($head->can('forceDelete', $own))->toBeFalse();
})->with('branchContent');

it('lets branch-admin view but never edit branch content', function (string $model) {
    $admin = User::factory()->branchAdmin($this->branch)->create();
    $own = $model::factory()->create(['branch_id' => $this->branch->id]);

    expect($admin->can('view', $own))->toBeTrue()
        ->and($admin->can('create', $model))->toBeFalse()
        ->and($admin->can('update', $own))->toBeFalse()
        ->and($admin->can('delete', $own))->toBeFalse();
})->with('branchContent');

it('denies members and null-branch staff on branch content', function (string $model) {
    $record = $model::factory()->create(['branch_id' => $this->branch->id]);
    $member = User::factory()->member($this->branch)->create();
    $noBranch = User::factory()->branchHead($this->branch)->create(['branch_id' => null]);

    foreach ([$member, $noBranch] as $user) {
        expect($user->can('viewAny', $model))->toBeFalse()
            ->and($user->can('view', $record))->toBeFalse()
            ->and($user->can('create', $model))->toBeFalse()
            ->and($user->can('update', $record))->toBeFalse()
            ->and($user->can('delete', $record))->toBeFalse();
    }
})->with('branchContent');

it('limits branch management to super-admin and own-branch viewing', function () {
    $super = User::factory()->superAdmin()->create();
    $head = User::factory()->branchHead($this->branch)->create();
    $admin = User::factory()->branchAdmin($this->branch)->create();
    $member = User::factory()->member($this->branch)->create();
    $noBranch = User::factory()->branchHead($this->branch)->create(['branch_id' => null]);

    expect($super->can('create', Branch::class))->toBeTrue()
        ->and($super->can('update', $this->other))->toBeTrue()
        ->and($super->can('delete', $this->other))->toBeTrue();

    foreach ([$head, $admin] as $user) {
        expect($user->can('viewAny', Branch::class))->toBeTrue()
            ->and($user->can('view', $this->branch))->toBeTrue()
            ->and($user->can('view', $this->other))->toBeFalse()
            ->and($user->can('create', Branch::class))->toBeFalse()
            ->and($user->can('update', $this->branch))->toBeFalse()
            ->and($user->can('delete', $this->branch))->toBeFalse()
            ->and($user->can('restore', $this->branch))->toBeFalse()
            ->and($user->can('forceDelete', $this->branch))->toBeFalse();
    }

    foreach ([$member, $noBranch] as $user) {
        expect($user->can('viewAny', Branch::class))->toBeFalse()
            ->and($user->can('view', $this->branch))->toBeFalse();
    }
});

it('brands the admin panel', function () {
    expect(Filament::getPanel('admin')->getBrandName())->toBe('COG Audio Ministry');
    $this->get('/admin/login')->assertOk()->assertSee('COG Audio Ministry');
});
