<?php

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('creates a verified super-admin', function () {
    $this->artisan('ministry:create-super-admin', ['--name' => 'Owner', '--email' => 'owner@example.test'])
        ->expectsQuestion('Password', 'Str0ng-Passw0rd!x')
        ->expectsQuestion('Confirm password', 'Str0ng-Passw0rd!x')
        ->assertSuccessful();

    $user = User::where('email', 'owner@example.test')->firstOrFail();
    expect($user->hasRole('super-admin'))->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->branch_id)->toBeNull();
});

it('rejects a duplicate email', function () {
    User::factory()->create(['email' => 'owner@example.test']);

    $this->artisan('ministry:create-super-admin', ['--name' => 'Owner', '--email' => 'owner@example.test'])
        ->expectsQuestion('Password', 'Str0ng-Passw0rd!x')
        ->expectsQuestion('Confirm password', 'Str0ng-Passw0rd!x')
        ->assertFailed();

    expect(User::where('email', 'owner@example.test')->count())->toBe(1);
});

it('seeds roles and branch-admins per branch (two in Dasma)', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Role::count())->toBe(3)
        ->and(Role::where('name', 'branch-head')->exists())->toBeFalse();

    Branch::all()->each(function (Branch $branch) {
        expect(User::role('branch-admin')->where('branch_id', $branch->id)->count())->toBe($branch->slug === 'dasma' ? 2 : 1);
    });
});
