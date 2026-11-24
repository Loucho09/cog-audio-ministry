<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

it('shows our churches section with logo and no satellite wording', function () {
    $response = $this->get('/')
        ->assertOk()
        ->assertSee('One sound, all our churches')
        ->assertSee('Church of God logo: We Exalt You Jesus', false)
        ->assertSee('cog-church-logo.png', false);

    foreach (['COG Dasma', 'COG Marilag', 'COG Trece', 'COG Silang', 'COG Jabez'] as $name) {
        $response->assertSee($name);
    }

    expect(strtolower($response->getContent()))->not->toContain('satellite');
});
