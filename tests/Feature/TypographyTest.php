<?php

it('loads the Schibsted Grotesk and JetBrains Mono fonts from Bunny', function () {
    $this->get('/')->assertOk()
        ->assertSee('family=schibsted-grotesk:400,500,600,700,800|jetbrains-mono:400,500', false)
        ->assertDontSee('montserrat', false)
        ->assertDontSee('mrs-saint-delafield', false)
        ->assertDontSee('family=inter', false);
});

it('sets the hero title as plain text with no script accent', function () {
    $dom = new DOMDocument;
    @$dom->loadHTML($this->get('/')->assertOk()->getContent());
    $xpath = new DOMXPath($dom);

    $h1 = $xpath->query('//h1[@id="hero-title"]')->item(0);

    expect(trim($h1->textContent))->toBe('We serve behind the sound.')
        ->and($xpath->query('//h1[@id="hero-title"]//*')->length)->toBe(0)
        ->and($xpath->query('//*[contains(@class, "font-script")]')->length)->toBe(0);
});
