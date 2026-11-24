<?php

use App\Models\Branch;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

it('shows the impact section with churches and services counts', function () {
    $branch = Branch::factory()->create(['is_main' => true]);
    Service::factory()->create(['branch_id' => $branch->id, 'day_of_week' => 0]);
    Service::factory()->create(['branch_id' => $branch->id, 'day_of_week' => 3]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Every sermon, every song, heard clearly.')
        ->assertSeeInOrder(['Churches served', '5'], false)
        ->assertSeeInOrder(['Services every week', '2'], false)
        ->assertDontSee('Years serving');
});

it('shows the years stat only when configured', function () {
    config(['ministry.years_serving' => 12]);

    $this->get('/')->assertOk()->assertSeeInOrder(['Years serving', '12'], false);
});
