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
        ->assertSee('Today')
        ->assertSeeInOrder(['Saturday, Nov 28', '5:30 PM', 'Empowered Night', 'Sunday, Nov 29', '5:30 AM', 'Sunday Service', '7:30 AM']);
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
    GalleryImage::factory()->count(5)->create();

    DB::enableQueryLog();
    $this->get('/')->assertOk();

    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(6);
});

it('serves the home route through HomeController', function () {
    $route = app('router')->getRoutes()->getByName('home');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('/')
        ->and($route->getActionName())->toBe(HomeController::class);
});
