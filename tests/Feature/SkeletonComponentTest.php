<?php

use Illuminate\Support\Facades\Blade;

it('renders every skeleton variant hidden from assistive tech', function (string $variant) {
    $html = Blade::render('<x-skeleton variant="'.$variant.'" />');

    expect($html)->toContain('aria-hidden="true"')
        ->and($html)->toContain('data-skeleton="'.$variant.'"')
        ->and($html)->toContain('skeleton');
})->with(['line', 'block', 'circle', 'card']);

it('renders one shimmer row per line, with a shorter last line', function () {
    $html = Blade::render('<x-skeleton variant="line" :lines="3" />');

    expect(substr_count($html, 'skeleton skeleton-text'))->toBe(3)
        ->and($html)->toContain('w-2/3');
});

it('falls back to a single line for unknown variants and clamps the line count', function () {
    expect(Blade::render('<x-skeleton variant="spinner" />'))->toContain('data-skeleton="line"')
        ->and(substr_count(Blade::render('<x-skeleton :lines="50" />'), 'skeleton skeleton-text'))->toBe(6);
});

it('uses the circle helper for avatars and keeps a single size square', function () {
    $html = Blade::render('<x-skeleton variant="circle" :width="48" />');

    expect($html)->toContain('skeleton-circle')
        ->and($html)->toContain('style="width: 48px; height: 48px"');
});

it('only accepts plain CSS lengths for width and height', function () {
    $html = Blade::render('<x-skeleton variant="block" width="10px; background: red" height="10rem" />');

    expect($html)->not->toContain('background: red')
        ->and($html)->toContain('height: 10rem');
});
