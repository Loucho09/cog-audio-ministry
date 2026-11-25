<?php

use App\Actions\AssignRole;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Branch;
use App\Models\GalleryImage;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->a = Branch::factory()->create();
    $this->b = Branch::factory()->create();
    $this->admin = User::factory()->branchAdmin($this->a)->create();
});

it('lets a branch-admin manage gallery and team in their own branch only', function () {
    $own = GalleryImage::factory()->create(['branch_id' => $this->a->id]);
    $other = GalleryImage::factory()->create(['branch_id' => $this->b->id]);
    $team = TeamMember::factory()->create(['branch_id' => $this->a->id]);
    $otherTeam = TeamMember::factory()->create(['branch_id' => $this->b->id]);

    expect(Gate::forUser($this->admin)->allows('create', GalleryImage::class))->toBeTrue()
        ->and(Gate::forUser($this->admin)->allows('update', $own))->toBeTrue()
        ->and(Gate::forUser($this->admin)->allows('delete', $own))->toBeTrue()
        ->and(Gate::forUser($this->admin)->denies('update', $other))->toBeTrue()
        ->and(Gate::forUser($this->admin)->allows('create', TeamMember::class))->toBeTrue()
        ->and(Gate::forUser($this->admin)->allows('update', $team))->toBeTrue()
        ->and(Gate::forUser($this->admin)->denies('update', $otherTeam))->toBeTrue();
});

it('lets a branch-admin manage own-branch members but not other branches', function () {
    $member = User::factory()->member($this->a)->create();
    $foreign = User::factory()->member($this->b)->create();

    expect(Gate::forUser($this->admin)->allows('view', $member))->toBeTrue()
        ->and(Gate::forUser($this->admin)->allows('update', $member))->toBeTrue()
        ->and(Gate::forUser($this->admin)->allows('setTemporaryPassword', $member))->toBeTrue()
        ->and(Gate::forUser($this->admin)->denies('view', $foreign))->toBeTrue()
        ->and(Gate::forUser($this->admin)->denies('update', $foreign))->toBeTrue();
});

it('stops a branch-admin from managing another admin, a super-admin or themselves', function () {
    $peer = User::factory()->branchAdmin($this->a)->create();
    $owner = User::factory()->superAdmin()->create();

    foreach ([$peer, $owner, $this->admin] as $target) {
        expect(Gate::forUser($this->admin)->denies('update', $target))->toBeTrue()
            ->and(Gate::forUser($this->admin)->denies('setTemporaryPassword', $target))->toBeTrue()
            ->and(Gate::forUser($this->admin)->denies('assignRole', $target))->toBeTrue();
    }
});

it('stops a branch-admin from assigning roles', function () {
    $member = User::factory()->member($this->a)->create();

    app(AssignRole::class)->handle($this->admin, $member, 'branch-admin');
})->throws(AuthorizationException::class);

it('ignores role, branch and email sent by a branch-admin and saves only the name', function () {
    Filament::setCurrentPanel('admin');
    $member = User::factory()->member($this->a)->create(['name' => 'Old', 'email' => 'old@example.test']);
    $this->actingAs($this->admin);

    Livewire::test(EditUser::class, ['record' => $member->id])
        ->fillForm(['name' => 'Renamed', 'email' => 'new@example.test', 'role' => 'super-admin', 'branch_id' => $this->b->id])
        ->call('save');

    $member->refresh();
    expect($member->name)->toBe('Renamed')
        ->and($member->email)->toBe('old@example.test')
        ->and($member->branch_id)->toBe($this->a->id)
        ->and($member->hasRole('member'))->toBeTrue();
});

it('cannot set a temporary password on a co-admin', function () {
    $peer = User::factory()->branchAdmin($this->a)->create();
    $hash = $peer->password;

    expect($this->admin->can('setTemporaryPassword', $peer))->toBeFalse()
        ->and($peer->fresh()->password)->toBe($hash);
});
