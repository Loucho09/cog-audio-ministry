<?php

it('loads the Montserrat and script fonts that match the logo', function () {
    $this->get('/')->assertOk()
        ->assertSee('family=montserrat:400,500,600,700,800|mrs-saint-delafield:400', false)
        ->assertDontSee('family=inter', false);
});

it('sets "Audio" in the hero title in the script font while keeping the full name readable', function () {
    $dom = new DOMDocument;
    @$dom->loadHTML($this->get('/')->assertOk()->getContent());
    $xpath = new DOMXPath($dom);

    $h1 = $xpath->query('//h1[@id="hero-title"]')->item(0);

    expect(trim($h1->textContent))->toBe(config('ministry.name'))
        ->and($xpath->query('//h1[@id="hero-title"]/span[contains(@class, "font-script")]')->length)->toBe(1);
});
