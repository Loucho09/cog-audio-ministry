<?php

// Returns the markup of the first <section> whose opening tag matches $pattern.
function homepageSection(string $html, string $pattern): string
{
    preg_match('/<section\b[^>]*'.$pattern.'.*?<\/section>/s', $html, $matches);

    return $matches[0] ?? '';
}

// Returns the href of the first link whose visible text is exactly $text, or null.
function homepageLinkHref(string $html, string $text): ?string
{
    preg_match('/<a\s[^>]*href="([^"]+)"[^>]*>\s*'.preg_quote($text, '/').'\s*<\/a>/', $html, $matches);

    return $matches[1] ?? null;
}

beforeEach(fn () => $this->withoutVite());

it('renders the hero H1 with the ministry name as the only H1 on the page', function () {
    $html = $this->get('/')->assertOk()->getContent();
    $hero = homepageSection($html, 'aria-labelledby="hero-title"');

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($hero)->toContain('<h1')
        ->and($hero)->toContain('>'.config('ministry.name').'</h1>')
        ->and($hero)->toContain(config('ministry.tagline'));
});

it('links the hero Join Us button to #join', function () {
    $html = $this->get('/')->assertOk()->getContent();
    $hero = homepageSection($html, 'aria-labelledby="hero-title"');

    expect(homepageLinkHref($hero, 'Join Us'))->toBe(route('home').'#join');
});

it('links the hero See Service Times button to #services', function () {
    $html = $this->get('/')->assertOk()->getContent();
    $hero = homepageSection($html, 'aria-labelledby="hero-title"');

    expect(homepageLinkHref($hero, 'See Service Times'))->toBe(route('home').'#services');
});

it('renders the cover banner in the hero with a webp source and a jpg fallback', function () {
    $html = $this->get('/')->assertOk()->getContent();
    $hero = homepageSection($html, 'aria-labelledby="hero-title"');

    expect($hero)->toContain('<picture>')
        ->and($hero)->toContain('srcset="'.asset('images/cover.webp').'"')
        ->and($hero)->toContain('src="'.asset('images/cover.jpg').'"')
        ->and($hero)->toContain('alt="COG Audio banner showing a lit mixing console beside the COG Audio logo"')
        ->and($hero)->toContain('fetchpriority="high"')
        ->and($hero)->not->toContain('loading="lazy"');
});

it('renders the about section with vision and mission placeholders', function () {
    $html = $this->get('/')->assertOk()->getContent();
    $about = homepageSection($html, 'id="about"');

    expect($about)->toContain('About Us')
        ->and($about)->toContain('Our Vision')
        ->and($about)->toContain('Our Mission')
        ->and(substr_count($about, 'Coming soon.'))->toBe(2);
});

it('renders the what we do section with the four team roles', function () {
    $html = $this->get('/')->assertOk()->getContent();
    $section = homepageSection($html, 'id="what-we-do"');

    expect($section)->toContain('What We Do')
        ->and(substr_count($section, '<h3'))->toBe(4);

    foreach (['FOH (Front of House)', 'Monitors', 'Livestream', 'Stage Tech'] as $role) {
        expect($section)->toContain('>'.$role.'</h3>');
    }
});

it('exposes the about and what-we-do sections as anchor targets', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('id="about"', false)
        ->assertSee('id="what-we-do"', false);
});
