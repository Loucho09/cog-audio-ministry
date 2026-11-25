<?php

use App\Models\Branch;
use App\Models\Service;
use App\Models\User;

it('redirects guests to login', function () {
    $this->get('/dashboard')->assertRedirect(route('login'));
});

it('lists the own branch services before other branches', function () {
    $own = Branch::factory()->create(['name' => 'Zeta Own']);
    $other = Branch::factory()->create(['name' => 'Alpha Other']);
    Service::factory()->create(['branch_id' => $own->id, 'name' => 'Own Service']);
    Service::factory()->create(['branch_id' => $other->id, 'name' => 'Other Service']);

    $user = User::factory()->member($own)->create();

    $this->actingAs($user)->get('/dashboard')
        ->assertOk()
        ->assertSeeInOrder(['Own Service', 'Other Service'])
        ->assertSee('Alpha Other');
});

it('shows the admin link only to admin roles', function () {
    $branch = Branch::factory()->create();

    $this->actingAs(User::factory()->member($branch)->create())
        ->get('/dashboard')->assertOk()->assertDontSee('href="/admin"', false);

    foreach (['branchHead', 'branchAdmin'] as $state) {
        $this->actingAs(User::factory()->{$state}($branch)->create())
            ->get('/dashboard')->assertOk()->assertSee('href="/admin"', false);
    }

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get('/dashboard')->assertOk()->assertSee('href="/admin"', false);
});

it('hides empty branch fields and shows filled ones', function () {
    $empty = Branch::factory()->create();
    $this->actingAs(User::factory()->member($empty)->create())
        ->get('/dashboard')->assertOk()
        ->assertDontSee('Address')
        ->assertDontSee('Visit our Facebook page')
        ->assertSee('has not added its details yet');

    $full = Branch::factory()->create(['address' => '123 Test St', 'facebook_url' => 'https://facebook.com/test']);
    $this->actingAs(User::factory()->member($full)->create())
        ->get('/dashboard')->assertOk()
        ->assertSee('123 Test St')
        ->assertSee('Visit our Facebook page')
        ->assertDontSee('has not added its details yet');
});
