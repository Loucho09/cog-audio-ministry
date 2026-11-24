<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

it('shows our churches section with logo background and no satellite wording', function () {
    $response = $this->get('/')
        ->assertOk()
        ->assertSee('One sound, all our churches')
        ->assertSee('aria-label="Church of God logo: We Exalt You Jesus"', false)
        ->assertSee('images/cog-church-logo.png', false)
        ->assertDontSee('cog-church-logo-256.png', false);

    foreach (['COG Dasma', 'COG Marilag', 'COG Trece', 'COG Silang', 'COG Jabez'] as $name) {
        $response->assertSee($name);
    }

    expect(strtolower($response->getContent()))->not->toContain('satellite');
});

it('renders per-church Facebook links safely and skips churches without one', function () {
    $response = $this->get('/')->assertOk();

    foreach ([
        'COG Trece' => 'https://www.facebook.com/COGTreceMartires',
        'COG Silang' => 'https://www.facebook.com/cogsilang',
        'COG Jabez' => 'https://www.facebook.com/churchofgodjabez',
    ] as $name => $url) {
        $response->assertSee('href="'.$url.'"', false)
            ->assertSee('aria-label="'.$name.' on Facebook (opens in a new tab)"', false);
    }

    $response->assertSee('rel="noopener noreferrer"', false)
        ->assertDontSee('COG Marilag on Facebook', false);
});
