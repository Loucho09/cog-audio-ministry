<?php

use App\Models\Branch;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

it('marks the homepage sections for scroll reveal', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('id="about" data-reveal')
        ->and($html)->toContain('id="what-we-do" data-reveal')
        ->and($html)->toContain('id="join" data-reveal')
        ->and(substr_count($html, 'data-reveal'))->toBeGreaterThanOrEqual(5);
});

it('never reveal-animates the hero (above the fold)', function () {
    $html = $this->get('/')->assertOk()->getContent();

    preg_match('/<section aria-labelledby="hero-title"[^>]*>/', $html, $matches);

    expect($matches)->toHaveCount(1)
        ->and($matches[0])->not->toContain('data-reveal');
});

it('marks the member dashboard cards for scroll reveal', function () {
    $branch = Branch::factory()->create();
    $html = $this->actingAs(User::factory()->member($branch)->create())
        ->get('/dashboard')
        ->assertOk()
        ->getContent();

    expect(substr_count($html, '<section data-reveal'))->toBe(6)
        ->and($html)->toContain('data-reveal-delay="80"');
});

it('keeps one polite live region for loading announcements on the public and member layouts', function () {
    $home = $this->get('/')->assertOk()->getContent();
    $dashboard = $this->actingAs(User::factory()->member(Branch::factory()->create())->create())
        ->get('/dashboard')
        ->assertOk()
        ->getContent();

    expect(substr_count($home, 'data-live-region'))->toBe(1)
        ->and(substr_count($dashboard, 'data-live-region'))->toBe(1);
});
