<?php

use App\Models\GalleryImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

it('renders a skeleton wrapper around the image with its src and alt', function () {
    $html = Blade::render('<x-skeleton-image src="/images/gallery/a.jpg" alt="Sound desk at Sunday service" />');

    expect($html)->toContain('data-skeleton-image')
        ->and($html)->toContain('class="relative overflow-hidden skeleton')
        ->and($html)->toContain('src="/images/gallery/a.jpg"')
        ->and($html)->toContain('alt="Sound desk at Sunday service"')
        ->and($html)->toContain('loading="lazy"')
        ->and($html)->toContain('decoding="async"')
        ->and($html)->toContain('role="img" aria-label="Sound desk at Sunday service"');
});

it('reserves the box from width and height so there is no layout shift', function () {
    $html = Blade::render('<x-skeleton-image src="/a.jpg" alt="Team member" width="128" height="128" />');

    expect($html)->toContain('aspect-ratio: 128 / 128')
        ->and($html)->toContain('width="128"')
        ->and($html)->toContain('height="128"');
});

it('passes sizes, srcset and other attributes through to the img, and keeps decorative images hidden from AT', function () {
    $html = Blade::render('<x-skeleton-image src="/a.jpg" alt="" sizes="100vw" srcset="/a-800.jpg 800w" fetchpriority="low" aria-hidden="true" class="h-full w-full" img-class="object-center" />');

    expect($html)->toContain('sizes="100vw"')
        ->and($html)->toContain('srcset="/a-800.jpg 800w"')
        ->and($html)->toContain('fetchpriority="low"')
        ->and($html)->toContain('alt=""')
        ->and($html)->toContain('aria-hidden="true"')
        ->and($html)->toContain('object-center')
        ->and($html)->not->toContain('role="img" aria-label=""');
});

it('starts the image hidden only through Alpine, with a fade and a calm error state', function () {
    $html = Blade::render('<x-skeleton-image src="/a.jpg" alt="Photo" loading="eager" />');

    expect($html)->toContain('loading="eager"')
        ->and($html)->toContain('transition-opacity duration-300')
        ->and($html)->toContain("'opacity-0': state !== 'loaded'")
        ->and($html)->toContain('x-ref="img"')
        ->and($html)->toContain('x-on:load')
        ->and($html)->toContain('state = \'loaded\'')
        ->and($html)->toContain('state = \'error\'');
});

it('requires an alt attribute', function () {
    // Blade wraps view errors in ViewException (an Exception), so match on the class and the message.
    expect(fn () => Blade::render('<x-skeleton-image src="/a.jpg" />'))
        ->toThrow(Exception::class, 'needs an alt attribute');
});

it('wraps published homepage gallery photos in skeleton images', function () {
    GalleryImage::factory()->create([
        'path' => 'images/gallery/a.jpg',
        'alt_text' => 'Sound desk at Sunday service',
        'is_published' => true,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('data-skeleton-image', false)
        ->assertSee('alt="Sound desk at Sunday service"', false);
});

it('wraps placeholder gallery photos in skeleton images too', function () {
    GalleryImage::factory()->create();

    $this->get('/')
        ->assertOk()
        ->assertSee('data-skeleton-image', false)
        ->assertSee('Photos coming soon. These are placeholders.');
});
