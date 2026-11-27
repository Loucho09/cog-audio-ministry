<?php

use App\Http\Controllers\HomeController;
use App\Models\Branch;
use App\Models\GalleryImage;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Database\Seeders\BranchSeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    config(['app.timezone' => 'Asia/Manila']);
    // Saturday 2026-11-28 10:00 Manila.
    $this->travelTo(CarbonImmutable::parse('2026-11-28 10:00', 'Asia/Manila'));
});

function seedServices(): void
{
    test()->seed([BranchSeeder::class, ServiceSeeder::class]);
}

it('shows today and upcoming services in chronological order', function () {
    seedServices();

    $this->get('/')
        ->assertOk()
        ->assertSeeInOrder(['Saturday, Nov 28', '5:30 PM', 'Empowered Night', 'Sunday, Nov 29', 'Sunday Service', '5:30 AM', '7:30 AM']);
});

it('puts the Today and Tomorrow badges on the correct dates only', function () {
    seedServices();

    $html = $this->get('/')->assertOk()->getContent();

    expect(substr_count($html, '>Today</span>'))->toBe(1)
        ->and(substr_count($html, '>Tomorrow</span>'))->toBe(1)
        ->and($html)->toMatch('/Saturday, Nov 28<\/h3>\s*<span[^>]*>Today<\/span>/')
        ->and($html)->toMatch('/Sunday, Nov 29<\/h3>\s*<span[^>]*>Tomorrow<\/span>/');
});

it('shows the branch name and weekly schedule', function () {
    seedServices();

    $this->get('/')->assertSee('At COG Dasmariñas')->assertSee('Weekly schedule');
});

it('hides inactive services', function () {
    seedServices();
    Service::where('name', 'Empowered Night')->update(['is_active' => false]);

    $this->get('/')->assertDontSee('Empowered Night');
});

it('hides other branches services', function () {
    seedServices();
    $trece = Branch::where('slug', 'trece')->first();
    Service::factory()->create([
        'branch_id' => $trece->id,
        'name' => 'Trece Special Gathering',
        'day_of_week' => 6,
        'start_time' => '18:00:00',
    ]);

    $this->get('/')->assertDontSee('Trece Special Gathering');
});

it('shows the services empty state when no branch exists', function () {
    $this->get('/')->assertOk()->assertSee('Service times will be posted soon.');
});

it('renders published gallery images and hides unpublished ones', function () {
    GalleryImage::factory()->create(['path' => 'images/gallery/a.jpg', 'alt_text' => 'Sound desk at Sunday service', 'is_published' => true]);
    GalleryImage::factory()->create(['path' => 'images/gallery/b.jpg', 'alt_text' => 'Hidden draft photo', 'is_published' => false]);

    $this->get('/')
        ->assertSee('Sound desk at Sunday service')
        ->assertDontSee('Hidden draft photo')
        ->assertSee('role="dialog"', false);
});

it('shows a placeholder note without a lightbox when all images are placeholders', function () {
    GalleryImage::factory()->create();

    $this->get('/')
        ->assertSee('Photos coming soon. These are placeholders.')
        ->assertDontSee('role="dialog"', false);
});

it('shows the gallery empty state', function () {
    $this->get('/')->assertSee('Photos coming soon.');
});

it('keeps the homepage query count small', function () {
    seedServices();
    foreach (range(1, 5) as $n) {
        GalleryImage::factory()->create(['path' => "images/gallery/real-{$n}.jpg"]);
    }

    DB::enableQueryLog();
    $this->get('/')->assertOk();

    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(5); // + weekly services list
});

it('does not render dates beyond the next 7 days', function () {
    seedServices();

    // Dec 5 is exactly 7 days after Nov 28, so it is outside the upcoming window and not shown.
    $this->get('/')
        ->assertOk()
        ->assertSee('Saturday, Nov 28')
        ->assertDontSee('Saturday, Dec 5')
        ->assertDontSee('more-service-dates')
        ->assertDontSee('Show more dates');
});

it('shows real weekly summary lines with the weekday', function () {
    seedServices();

    $this->get('/')
        ->assertSee('Saturday', false)
        ->assertSeeInOrder(['Saturday', 'Empowered Night', '5:30 PM'])
        ->assertSeeInOrder(['Tuesday', 'Revival Night', '7:00 PM'])
        ->assertSee('Sunday', false)
        ->assertSeeInOrder(['Sunday', 'Sunday Service', '5:30 AM', '7:30 AM', '11:30 AM']);
});

it('puts a single real photo in the lightbox without prev and next', function () {
    $q = chr(92).'u0022';
    GalleryImage::factory()->create(['path' => 'images/gallery/only.jpg', 'alt_text' => 'Only photo']);

    $this->get('/')
        ->assertSee('role="dialog"', false)
        ->assertSee('only.jpg', false)
        ->assertSee('placeholder'.$q.':false', false)
        ->assertDontSee('aria-label="Previous photo"', false)
        ->assertDontSee('aria-label="Next photo"', false)
        ->assertSee('trapTab', false);
});

it('shows prev and next with several real photos and keeps placeholders out of the lightbox', function () {
    $q = chr(92).'u0022';
    GalleryImage::factory()->create(['path' => 'images/placeholders/gallery-1.svg', 'alt_text' => 'Placeholder shot']);
    GalleryImage::factory()->create(['path' => 'images/gallery/one.jpg', 'alt_text' => 'Real one']);
    GalleryImage::factory()->create(['path' => 'images/gallery/two.jpg', 'alt_text' => 'Real two']);

    $html = $this->get('/')
        ->assertSee('aria-label="Previous photo"', false)
        ->assertSee('aria-label="Next photo"', false)
        ->assertSee('show(0, $el)', false)
        ->assertSee('show(1, $el)', false)
        ->assertDontSee('show(2, $el)', false)
        ->getContent();

    // The @js payload holds only the two real photos.
    expect(substr_count($html, 'placeholder'.$q.':false'))->toBe(2)
        ->and($html)->not->toContain('placeholder'.$q.':true');
});

it('serves the home route through HomeController', function () {
    $route = app('router')->getRoutes()->getByName('home');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('/')
        ->and($route->getActionName())->toBe(HomeController::class);
});

it('renders data-day, data-time and data-name hooks for the next-service script', function () {
    seedServices();

    $html = $this->get('/')->assertOk()->getContent();

    // Tuesday (2) Revival Night at 19:00 and Sunday (0) first service at 05:30, both 24h.
    expect($html)->toMatch('/data-day="2"\s+data-time="19:00"\s+data-name="Revival Night"/')
        ->and($html)->toMatch('/data-day="0"\s+data-time="05:30"\s+data-name="Sunday Service"/')
        ->and($html)->toContain('js-led');
});
