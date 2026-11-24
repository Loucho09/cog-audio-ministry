<?php

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(fn () => $this->seed(RoleSeeder::class));

it('does not create demo users outside local/testing', function () {
    app()->detectEnvironment(fn () => 'production');

    try {
        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])->assertSuccessful();
    } finally {
        app()->detectEnvironment(fn () => 'testing');
    }

    expect(User::where('email', 'superadmin@example.test')->exists())->toBeFalse();
});

it('throttles PUT /password after 6 attempts', function () {
    $user = User::factory()->member(Branch::factory()->create())->create();

    for ($i = 0; $i < 6; $i++) {
        $this->actingAs($user)->put('/password', ['current_password' => 'wrong']);
    }

    $this->actingAs($user)->put('/password', ['current_password' => 'wrong'])->assertStatus(429);
});

it('shows the Admin panel link only to users who can access it', function () {
    $broken = User::factory()->branchAdmin(Branch::factory()->create())->create(['branch_id' => null]);
    $this->actingAs($broken)->get('/dashboard')->assertOk()->assertDontSee('Admin panel');

    $super = User::factory()->superAdmin()->create();
    $this->actingAs($super)->get('/dashboard')->assertOk()->assertSee('Admin panel');
});
