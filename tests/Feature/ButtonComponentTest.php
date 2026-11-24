<?php

use Illuminate\Support\Facades\Blade;

it('renders a link when href is set', function () {
    $html = Blade::render('<x-site.button href="/join">Join</x-site.button>');

    expect($html)->toContain('<a href="/join"')
        ->and($html)->not->toContain('<button');
});

it('renders a button with type="button" when href is missing', function () {
    $html = Blade::render('<x-site.button>Open</x-site.button>');

    expect($html)->toContain('<button type="button"')
        ->and($html)->not->toContain('<a ');
});

it('renders a single type="submit" when type is submit', function () {
    $html = Blade::render('<x-site.button type="submit">Send</x-site.button>');

    expect(substr_count($html, 'type="submit"'))->toBe(1)
        ->and($html)->not->toContain('type="button"');
});

it('applies the secondary variant classes', function () {
    $html = Blade::render('<x-site.button variant="secondary">Times</x-site.button>');

    expect($html)->toContain('border-primary-dark')
        ->and($html)->toContain('bg-surface')
        ->and($html)->toContain('text-primary-dark')
        ->and($html)->not->toContain('text-surface');
});

it('falls back to the primary variant for an unknown variant', function () {
    $html = Blade::render('<x-site.button variant="does-not-exist">Go</x-site.button>');

    expect($html)->toContain('bg-primary-dark text-surface')
        ->and($html)->toContain('<button type="button"');
});

it('falls back to the default size for an unknown size', function () {
    $html = Blade::render('<x-site.button size="does-not-exist">Go</x-site.button>');

    expect($html)->toContain('px-4 py-2 text-sm');
});

it('uses the primary-dark focus ring', function () {
    $html = Blade::render('<x-site.button>Go</x-site.button>');

    expect($html)->toContain('focus-visible:ring-primary-dark')
        ->and($html)->not->toContain('focus-visible:ring-accent');
});

it('merges extra classes with the component classes', function () {
    $html = Blade::render('<x-site.button class="w-full sm:w-auto">Go</x-site.button>');

    expect(substr_count($html, 'class="'))->toBe(1)
        ->and($html)->toContain('w-full')
        ->and($html)->toContain('sm:w-auto')
        ->and($html)->toContain('rounded-full')
        ->and($html)->toContain('bg-primary-dark');
});
