<?php

use Illuminate\Support\Facades\Blade;

it('renders the section wrapper with its id, labelled-by and container', function () {
    $html = Blade::render('<x-section id="about" tone="surface"><p>Body</p></x-section>');

    expect($html)->toContain('<section')
        ->toContain('id="about"')
        ->toContain('aria-labelledby="about-title"')
        ->toContain('py-24')
        ->toContain('lg:py-32')
        ->toContain('bg-surface')
        ->toContain('mx-auto max-w-7xl px-6 lg:px-8')
        ->toContain('<p>Body</p>');
});

it('renders the console tone as a dark band and passes through extra attributes', function () {
    $html = Blade::render('<x-section id="join" tone="console" data-reveal>x</x-section>');

    expect($html)->toContain('bg-console')
        ->toContain('text-white')
        ->toContain('data-reveal');
});

it('renders the section heading with its channel label and h2 id', function () {
    $html = Blade::render('<x-section-heading id="about" channel="01" eyebrow="About" title="Who we are" description="Short line." />');

    expect($html)->toContain('CH 01 — About')
        ->toContain('<h2')
        ->toContain('id="about-title"')
        ->toContain('Who we are')
        ->toContain('Short line.')
        ->toContain('t-h2');
});

it('renders the split heading layout and omits the label when there is no eyebrow or channel', function () {
    $split = Blade::render('<x-section-heading id="team" title="Team" align="split" description="Desc" />');
    $bare = Blade::render('<x-section-heading id="team" title="Team" />');

    expect($split)->toContain('lg:grid-cols-12')
        ->toContain('lg:col-span-7')
        ->and($bare)->not->toContain('CH ')
        ->not->toContain('<p class="t-label">');
});

it('renders an inline svg for known icon names and nothing for unknown ones', function () {
    $known = Blade::render('<x-tabler-icon name="mail" class="h-6 w-6" />');
    $unknown = trim(Blade::render('<x-tabler-icon name="does-not-exist" />'));

    expect($known)->toContain('<svg')
        ->toContain('stroke-width="1.5"')
        ->toContain('aria-hidden="true"')
        ->toContain('h-6 w-6')
        ->and($unknown)->toBe('');
});

it('renders the button as a link when href is set and as a button otherwise', function () {
    $link = Blade::render('<x-button href="/join">Join us</x-button>');
    $button = Blade::render('<x-button>Send</x-button>');

    expect($link)->toContain('<a href="/join"')
        ->toContain('Join us')
        ->toContain('rounded-console')
        ->and($button)->toContain('<button')
        ->toContain('type="button"');
});
