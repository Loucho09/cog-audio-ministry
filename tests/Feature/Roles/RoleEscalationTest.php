<?php

use App\Actions\AssignRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(fn () => $this->seed(RoleSeeder::class));

it('ignores roles and branch_id sent to PATCH /profile', function () {
    $branch = Branch::factory()->create();
    $other = Branch::factory()->create();
    $user = User::factory()->member($branch)->create();

    $this->actingAs($user)->patch('/profile', [
        'name' => 'New Name',
        'email' => $user->email,
        'roles' => ['super-admin'],
        'role' => 'super-admin',
        'branch_id' => $other->id,
    ])->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->name)->toBe('New Name')
        ->and($user->branch_id)->toBe($branch->id)
        ->and($user->getRoleNames()->all())->toBe(['member']);
});

it('lets a super-admin assign roles', function () {
    $branch = Branch::factory()->create();
    $actor = User::factory()->superAdmin()->create();
    $target = User::factory()->member($branch)->create();

    app(AssignRole::class)->handle($actor, $target, 'branch-admin');

    expect($target->fresh()->getRoleNames()->all())->toBe(['branch-admin']);
});

it('denies role assignment to non super-admins', function (string $state) {
    $branch = Branch::factory()->create();
    $actor = User::factory()->{$state}($branch)->create();
    $target = User::factory()->member($branch)->create();

    app(AssignRole::class)->handle($actor, $target, 'branch-head');
})->with(['branchHead', 'branchAdmin', 'member'])->throws(AuthorizationException::class);

it('rejects unknown roles', function () {
    $actor = User::factory()->superAdmin()->create();
    $target = User::factory()->create();

    app(AssignRole::class)->handle($actor, $target, 'god-mode');
})->throws(AuthorizationException::class);

it('blocks the last super-admin from demoting themselves', function () {
    $actor = User::factory()->superAdmin()->create();

    try {
        app(AssignRole::class)->handle($actor, $actor, 'member');
    } catch (AuthorizationException) {
        // expected
    }

    expect($actor->fresh()->hasRole('super-admin'))->toBeTrue();

    User::factory()->superAdmin()->create();
    app(AssignRole::class)->handle($actor, $actor, 'member');

    expect($actor->fresh()->hasRole('super-admin'))->toBeFalse();
});
