<?php

use App\Models\Branch;
use App\Models\User;

it('redirects guests from /admin to login', function () {
    $this->get('/admin')->assertRedirect();
    expect($this->get('/admin')->headers->get('Location'))->toContain('login');
});

it('forbids members', function () {
    $user = User::factory()->member(Branch::factory()->create())->create();

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('forbids a branch-head without a branch', function () {
    $user = User::factory()->branchHead(Branch::factory()->create())->create(['branch_id' => null]);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('allows admin roles', function (string $state) {
    $branch = Branch::factory()->create();
    $user = User::factory()->{$state}($branch)->create();

    $this->actingAs($user)->get('/admin')->assertOk();
})->with(['branchHead', 'branchAdmin']);

it('allows a super-admin', function () {
    $this->actingAs(User::factory()->superAdmin()->create())->get('/admin')->assertOk();
});

it('throttles login after the route limit', function () {
    for ($i = 0; $i < 6; $i++) {
        $this->post('/login', ['email' => 'nobody@example.test', 'password' => 'wrong']);
    }

    $this->post('/login', ['email' => 'nobody@example.test', 'password' => 'wrong'])
        ->assertStatus(429);
});
