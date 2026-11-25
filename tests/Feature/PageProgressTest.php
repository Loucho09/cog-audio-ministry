<?php

use App\Models\Branch;
use App\Models\User;

it('includes the page progress bar on the public homepage', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('data-page-progress', false)
        ->assertSee('data-page-progress-status', false);
});

it('includes the page progress bar on the login page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('data-page-progress', false);
});

it('includes the page progress bar on the member dashboard', function () {
    $branch = Branch::factory()->create();

    $this->actingAs(User::factory()->member($branch)->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-page-progress', false);
});
