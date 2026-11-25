<?php

use App\Models\Branch;
use App\Models\JoinApplication;
use App\Models\User;
use Database\Seeders\JoinApplicationSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->branchA = Branch::factory()->create();
    $this->branchB = Branch::factory()->create();
    $this->appA = JoinApplication::factory()->pending()->create(['branch_id' => $this->branchA->id]);
    $this->appB = JoinApplication::factory()->pending()->create(['branch_id' => $this->branchB->id]);
});

it('ignores guarded columns on mass assignment', function () {
    $user = User::factory()->create();
    $app = JoinApplication::create([
        'branch_id' => $this->branchA->id,
        'full_name' => 'Test Person',
        'age' => 20,
        'contact_number' => '09123456789',
        'email' => 'x@example.test',
        'consented_at' => now(),
        'status' => 'approved',
        'reviewed_by' => $user->id,
        'reviewed_at' => now(),
        'user_id' => $user->id,
    ])->fresh();

    expect($app->status)->toBe('pending')
        ->and($app->reviewed_by)->toBeNull()
        ->and($app->reviewed_at)->toBeNull()
        ->and($app->user_id)->toBeNull();
});

it('casts attributes', function () {
    $app = $this->appA->fresh();

    expect($app->age)->toBeInt()
        ->and($app->consented_at)->toBeInstanceOf(Carbon::class);

    $reviewed = JoinApplication::factory()->approved()->create(['branch_id' => $this->branchA->id])->fresh();
    expect($reviewed->reviewed_at)->toBeInstanceOf(Carbon::class);
});

it('scopes the query by user', function () {
    $head = User::factory()->branchAdmin($this->branchA)->create();
    $admin = User::factory()->branchAdmin($this->branchA)->create();
    $super = User::factory()->superAdmin()->create();

    expect(JoinApplication::visibleTo($head)->pluck('id')->all())->toBe([$this->appA->id])
        ->and(JoinApplication::visibleTo($admin)->pluck('id')->all())->toBe([$this->appA->id])
        ->and(JoinApplication::visibleTo($super)->count())->toBe(2)
        ->and(JoinApplication::visibleTo(null)->count())->toBe(0);
});

it('lets branch staff view only their own branch', function (string $state) {
    $user = User::factory()->{$state}($this->branchA)->create();

    expect($user->can('viewAny', JoinApplication::class))->toBeTrue()
        ->and($user->can('view', $this->appA))->toBeTrue()
        ->and($user->can('view', $this->appB))->toBeFalse()
        ->and($user->can('approve', $this->appB))->toBeFalse()
        ->and($user->can('reject', $this->appB))->toBeFalse();
})->with(['branchAdmin']);

it('lets super-admin view any branch', function () {
    $super = User::factory()->superAdmin()->create();

    expect($super->can('view', $this->appA))->toBeTrue()
        ->and($super->can('view', $this->appB))->toBeTrue();
});

it('denies members everything', function () {
    $member = User::factory()->member($this->branchA)->create();

    expect($member->can('viewAny', JoinApplication::class))->toBeFalse()
        ->and($member->can('view', $this->appA))->toBeFalse()
        ->and($member->can('approve', $this->appA))->toBeFalse()
        ->and(JoinApplication::visibleTo($member)->count())->toBe(0);
});

it('denies approve and reject on non-pending applications', function (string $state) {
    $head = User::factory()->branchAdmin($this->branchA)->create();
    $app = JoinApplication::factory()->{$state}()->create(['branch_id' => $this->branchA->id]);

    expect($head->can('view', $app))->toBeTrue()
        ->and($head->can('approve', $app))->toBeFalse()
        ->and($head->can('reject', $app))->toBeFalse();
})->with(['approved', 'rejected']);

it('gives branch staff without a branch nothing', function (string $state) {
    $user = User::factory()->{$state}($this->branchA)->create();
    $user->forceFill(['branch_id' => null])->save();
    $user = $user->fresh();

    expect(JoinApplication::visibleTo($user)->count())->toBe(0)
        ->and($user->can('viewAny', JoinApplication::class))->toBeFalse()
        ->and($user->can('view', $this->appA))->toBeFalse();
})->with(['branchAdmin']);

it('blocks the reverse cross-branch case', function () {
    $head = User::factory()->branchAdmin($this->branchB)->create();

    expect($head->can('view', $this->appB))->toBeTrue()
        ->and($head->can('view', $this->appA))->toBeFalse()
        ->and(JoinApplication::visibleTo($head)->pluck('id')->all())->toBe([$this->appB->id]);
});

it('never lets branch-admin create or update', function () {
    $head = User::factory()->branchAdmin($this->branchA)->create();

    expect($head->can('create', JoinApplication::class))->toBeFalse()
        ->and($head->can('update', $this->appA))->toBeFalse();
});

it('does not duplicate applications when the seeder runs twice', function () {
    Branch::factory()->create();
    $this->seed(JoinApplicationSeeder::class);
    $count = JoinApplication::count();
    $this->seed(JoinApplicationSeeder::class);

    expect($count)->toBe(6)
        ->and(JoinApplication::count())->toBe($count);
});

it('currently lets super-admin approve non-pending via Gate::before', function () {
    // Pinned behavior: Gate::before bypasses the policy status check.
    // The approval action must enforce isPending() itself.
    $super = User::factory()->superAdmin()->create();
    $app = JoinApplication::factory()->approved()->create(['branch_id' => $this->branchA->id]);

    expect($super->can('approve', $app))->toBeTrue();
});

it('allows approve and reject on own-branch pending but never delete', function () {
    $head = User::factory()->branchAdmin($this->branchA)->create();

    expect($head->can('approve', $this->appA))->toBeTrue()
        ->and($head->can('reject', $this->appA))->toBeTrue()
        ->and($head->can('delete', $this->appA))->toBeFalse();
});
