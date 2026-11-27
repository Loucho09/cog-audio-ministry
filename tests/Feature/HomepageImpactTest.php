<?php

use App\Models\Branch;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

it('shows the about readouts with churches and services counts', function () {
    $branch = Branch::factory()->create(['is_main' => true]);
    Branch::factory()->count(4)->create();
    Service::factory()->create(['branch_id' => $branch->id, 'day_of_week' => 0]);
    Service::factory()->create(['branch_id' => $branch->id, 'day_of_week' => 3]);

    $this->get('/')
        ->assertOk()
        ->assertSee('data-count="5"', false)
        ->assertSeeInOrder(['Churches served', '<span class="sr-only">5</span>'], false)
        ->assertSeeInOrder(['Services every week', '<span class="sr-only">2</span>'], false);
});

it('hides the years stat when not configured', function () {
    config(['ministry.years_serving' => null]);

    $this->get('/')->assertOk()->assertDontSee('Years serving');
});

it('shows the years stat only when configured', function () {
    config(['ministry.years_serving' => 12]);

    $this->get('/')->assertOk()->assertSeeInOrder(['Years serving', '12'], false);
});
