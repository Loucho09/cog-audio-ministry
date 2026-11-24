<?php

use App\Models\Branch;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    config(['app.timezone' => 'Asia/Manila']);
    $this->travelTo(CarbonImmutable::parse('2026-11-28 10:00', 'Asia/Manila'));
    $branch = Branch::factory()->create(['name' => 'COG Dasma', 'is_main' => true]);
    Service::factory()->create([
        'branch_id' => $branch->id,
        'name' => 'Revival Night',
        'day_of_week' => 5,
        'start_time' => '19:00:00',
    ]);
});

it('hides the directions link when no address is set', function () {
    config(['ministry.address' => null]);

    $this->get('/')->assertOk()
        ->assertSee('Add to calendar')
        ->assertDontSee('Get directions')
        ->assertDontSee('google.com/maps/dir');
});

it('shows an encoded directions link when an address is set', function () {
    config(['ministry.address' => '123 Sample St, Dasmariñas & Cavite']);

    $url = 'https://www.google.com/maps/dir/?api=1&amp;destination='.rawurlencode('123 Sample St, Dasmariñas & Cavite');

    $this->get('/')->assertOk()
        ->assertSee('Get directions')
        ->assertSee('(opens Google Maps)')
        ->assertSee($url, false);
});
