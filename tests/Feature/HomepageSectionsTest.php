<?php

// Parses the homepage HTML into a DOMXPath. HTML5 tags (section, picture, source)
// are not known to libxml, but they still parse as elements. Parser warnings are
// captured internally so they do not fail the test.
function homepageXPath(string $html): DOMXPath
{
    $previous = libxml_use_internal_errors(true);

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->loadHTML('<?xml encoding="UTF-8">'.$html);

    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    return new DOMXPath($dom);
}

// Normalised text content of the first node matching $query, or null.
function homepageText(DOMXPath $xpath, string $query, ?DOMNode $context = null): ?string
{
    $nodes = $xpath->query($query, $context);

    return $nodes && $nodes->length > 0
        ? trim(preg_replace('/\s+/', ' ', $nodes->item(0)->textContent))
        : null;
}

beforeEach(fn () => $this->withoutVite());

it('renders exactly one h1 with the ministry name in the hero', function () {
    $xpath = homepageXPath($this->get('/')->assertOk()->getContent());

    $h1s = $xpath->query('//h1');

    expect($h1s->length)->toBe(1)
        ->and(homepageText($xpath, '//h1'))->toBe('We serve behind the sound.')
        ->and(homepageText($xpath, '//section[@aria-labelledby="hero-title"]//p[contains(normalize-space(), "CH 00")]'))->toContain(config('ministry.name'))
        ->and(homepageText($xpath, '//section[@aria-labelledby="hero-title"]'))->toContain(config('ministry.tagline'));
});

it('links the hero buttons to the join form and #services', function () {
    $xpath = homepageXPath($this->get('/')->assertOk()->getContent());

    $hero = '//section[@aria-labelledby="hero-title"]';

    expect($xpath->evaluate("string($hero//a[normalize-space()='Join Us']/@href)"))
        ->toBe(route('join.create'))
        ->and($xpath->evaluate("string($hero//a[normalize-space()='See Service Times']/@href)"))
        ->toBe(route('home').'#services');
});

it('renders the about section as an anchor target with its heading and the what-we-do section with five roles', function () {
    $xpath = homepageXPath($this->get('/')->assertOk()->getContent());

    $about = $xpath->query('//section[@id="about"][@aria-labelledby="about-title"]');

    expect($about->length)->toBe(1)
        ->and(homepageText($xpath, '//section[@id="about"]//h2'))->toBe('Every service has a crew you never see.')
        ->and($xpath->query('//section[@id="what-we-do"][@aria-labelledby="what-we-do-title"]')->length)->toBe(1)
        ->and($xpath->query('//section[@id="what-we-do"]//h3')->length)->toBe(5);
});

it('shows the vision and mission placeholders in the about section', function () {
    $xpath = homepageXPath($this->get('/')->assertOk()->getContent());

    $placeholders = $xpath->query('//section[@id="about"]//p[normalize-space()="Coming soon."]');

    expect(homepageText($xpath, '//section[@id="about"]//h3[@id="vision-title"]'))->toBe('Our Vision')
        ->and(homepageText($xpath, '//section[@id="about"]//h3[@id="mission-title"]'))->toBe('Our Mission')
        ->and($placeholders->length)->toBe(2);
});

it('renders the hero background as a responsive, decorative picture with a jpg fallback', function () {
    $xpath = homepageXPath($this->get('/')->assertOk()->getContent());

    $picture = '//section[@aria-labelledby="hero-title"]//picture[@data-hero-poster]';
    $source = $xpath->query($picture.'/source');
    $img = $xpath->query($picture.'/img');

    expect($xpath->query('//section[@aria-labelledby="hero-title"]//picture')->length)->toBe(1)
        ->and($source->length)->toBe(1)
        ->and($source->item(0)->getAttribute('type'))->toBe('image/webp')
        ->and($source->item(0)->getAttribute('srcset'))->toBe(
            asset('images/hero-console-800.webp').' 800w, '
            .asset('images/hero-console-1600.webp').' 1600w'
        )
        ->and($source->item(0)->getAttribute('sizes'))->toBe('(min-width: 1024px) 58vw, 100vw')
        ->and($img->length)->toBe(1)
        ->and($img->item(0)->getAttribute('src'))->toBe(asset('images/hero-console-1600.jpg'))
        ->and($img->item(0)->getAttribute('width'))->toBe('1600')
        ->and($img->item(0)->getAttribute('height'))->toBe('1724')
        ->and($img->item(0)->getAttribute('fetchpriority'))->toBe('high')
        ->and($img->item(0)->getAttribute('decoding'))->toBe('async')
        ->and($img->item(0)->getAttribute('loading'))->toBe('')
        ->and($img->item(0)->hasAttribute('alt'))->toBeTrue()
        ->and($img->item(0)->getAttribute('alt'))->toBe('')
        ->and($img->item(0)->getAttribute('class'))->toContain('object-contain');
});

it('ships the console poster (1600 and 800 webp, 1600 jpg) in public/images', function () {
    expect(file_exists(public_path('images/hero-console-1600.jpg')))->toBeTrue()
        ->and(file_exists(public_path('images/hero-console-1600.webp')))->toBeTrue()
        ->and(file_exists(public_path('images/hero-console-800.webp')))->toBeTrue();
});

it('renders the hero decorative shapes without blur-3xl', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->not->toContain('blur-3xl');
});

it('shows the theme verse and its reference in the hero', function () {
    $xpath = homepageXPath($this->get('/')->assertOk()->getContent());

    $figure = '//section[@aria-labelledby="hero-title"]//figure';

    expect(homepageText($xpath, $figure.'//blockquote'))->toContain(config('ministry.verse.text'))
        ->and(homepageText($xpath, $figure.'//figcaption'))->toBe(config('ministry.verse.reference'));
});

it('hides the theme verse when no verse text is set', function () {
    config(['ministry.verse.text' => null]);

    $xpath = homepageXPath($this->get('/')->assertOk()->getContent());

    expect($xpath->query('//section[@aria-labelledby="hero-title"]//figure')->length)->toBe(0);
});

it('shows the verse without a caption when no reference is set', function () {
    config(['ministry.verse.reference' => null]);

    $xpath = homepageXPath($this->get('/')->assertOk()->getContent());

    $figure = '//section[@aria-labelledby="hero-title"]//figure';

    expect($xpath->query($figure)->length)->toBe(1)
        ->and($xpath->query($figure.'//figcaption')->length)->toBe(0);
});

it('hides every section backdrop from assistive technology', function () {
    $xpath = homepageXPath($this->get('/')->assertOk()->getContent());

    // Our variants only; Tailwind's own backdrop-blur-* utilities are not decorative layers.
    $backdrops = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " backdrop-") and not(contains(@class, "backdrop-blur"))]/parent::*');

    // The redesigned sections use none; if one is ever added it must be hidden from assistive technology.
    foreach ($backdrops as $backdrop) {
        expect($backdrop->getAttribute('aria-hidden'))->toBe('true');
    }
});

it('renders every homepage section in the console order with its channel label', function () {
    $xpath = homepageXPath($this->get('/')->assertOk()->getContent());

    $ids = [];
    foreach ($xpath->query('//main//section[@id]') as $section) {
        $ids[] = $section->getAttribute('id');
    }

    // Team is behind a config flag (off by default), so it is not in this list.
    expect($ids)->toBe(['about', 'what-we-do', 'services', 'churches', 'gallery', 'join', 'contact']);

    foreach (['CH 01', 'CH 02', 'CH 03', 'CH 04', 'CH 05', 'CH 07'] as $label) {
        expect($xpath->evaluate('count(//main//p[contains(normalize-space(), "'.$label.'")])'))->toBeGreaterThan(0);
    }

    // Retired section markup is gone from the homepage.
    expect($xpath->query('//*[@id="impact" or @id="updates" or @data-marquee]')->length)->toBe(0);
});
