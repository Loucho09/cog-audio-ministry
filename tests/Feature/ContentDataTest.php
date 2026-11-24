<?php

use App\Actions\UpcomingServices;
use App\Models\Branch;
use App\Models\Faq;
use App\Models\GalleryImage;
use App\Models\Service;
use App\Models\TeamMember;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;

function at(string $datetime): CarbonImmutable
{
    return CarbonImmutable::parse($datetime, 'Asia/Manila');
}

it('runs the seeders', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Branch::count())->toBe(5)
        ->and(Branch::main()->count())->toBe(1)
        ->and(Branch::mainBranch()->slug)->toBe('dasma')
        ->and(Service::count())->toBe(10)
        ->and(Service::where('branch_id', Branch::mainBranch()->id)->count())->toBe(10)
        ->and(Service::where('name', 'Sunday Service')->count())->toBe(7)
        ->and(Faq::published()->count())->toBeGreaterThanOrEqual(4)
        ->and(GalleryImage::published()->count())->toBe(6)
        ->and(TeamMember::count())->toBe(0);
});

it('uses the Manila timezone by default', function () {
    expect(config('app.timezone'))->toBe('Asia/Manila');
});

it('computes nextOccurrence later today', function () {
    $service = Service::factory()->make(['day_of_week' => 3, 'start_time' => '19:00:00']);
    $next = $service->nextOccurrence(at('2026-11-25 10:00')); // Wednesday

    expect($next->format('Y-m-d H:i'))->toBe('2026-11-25 19:00');
});

it('rolls nextOccurrence to next week when today has passed', function () {
    $service = Service::factory()->make(['day_of_week' => 3, 'start_time' => '19:00:00']);
    $next = $service->nextOccurrence(at('2026-11-25 20:00'));

    expect($next->format('Y-m-d H:i'))->toBe('2026-12-02 19:00');
});

it('computes nextOccurrence for a different weekday', function () {
    $service = Service::factory()->make(['day_of_week' => 0, 'start_time' => '05:30:00']);
    $next = $service->nextOccurrence(at('2026-11-25 10:00')); // Wed -> Sun

    expect($next->format('Y-m-d H:i'))->toBe('2026-11-29 05:30');
});

it('lists upcoming services sorted over 4 weeks and skips inactive ones', function () {
    $branch = Branch::factory()->create();
    Service::factory()->for($branch)->create(['name' => 'Wed', 'day_of_week' => 3, 'start_time' => '19:00:00']);
    Service::factory()->for($branch)->create(['name' => 'Sun', 'day_of_week' => 0, 'start_time' => '05:30:00']);
    Service::factory()->for($branch)->create(['name' => 'Off', 'day_of_week' => 2, 'is_active' => false]);
    Service::factory()->create(['name' => 'Other branch', 'day_of_week' => 1]);

    $items = UpcomingServices::forBranch($branch, 4, at('2026-11-25 10:00'));

    expect($items)->toHaveCount(8)
        ->and($items->pluck('service.name')->unique()->sort()->values()->all())->toBe(['Sun', 'Wed'])
        ->and($items->first()['service']->name)->toBe('Wed')
        ->and($items->first()['starts_at']->format('Y-m-d H:i'))->toBe('2026-11-25 19:00');

    $times = $items->pluck('starts_at')->map->getTimestamp()->all();
    $sorted = $times;
    sort($sorted);
    expect($times)->toBe($sorted);
});

it('orders featured team members full-time first', function () {
    TeamMember::factory()->create(['name' => 'B', 'is_featured' => true, 'is_full_time' => false, 'sort_order' => 1]);
    TeamMember::factory()->create(['name' => 'C', 'is_featured' => true, 'is_full_time' => true, 'sort_order' => 2]);
    TeamMember::factory()->create(['name' => 'A', 'is_featured' => true, 'is_full_time' => true, 'sort_order' => 2]);
    TeamMember::factory()->create(['name' => 'Hidden', 'is_featured' => false, 'is_full_time' => true]);

    expect(TeamMember::featured()->pluck('name')->all())->toBe(['A', 'C', 'B']);
});

it('returns only published faqs and gallery images in order', function () {
    Faq::factory()->create(['question' => 'Second', 'sort_order' => 2]);
    Faq::factory()->create(['question' => 'First', 'sort_order' => 1]);
    Faq::factory()->create(['question' => 'Hidden', 'is_published' => false]);
    GalleryImage::factory()->create(['title' => 'Two', 'sort_order' => 2]);
    GalleryImage::factory()->create(['title' => 'One', 'sort_order' => 1]);
    GalleryImage::factory()->create(['title' => 'Hidden', 'is_published' => false]);

    expect(Faq::published()->pluck('question')->all())->toBe(['First', 'Second'])
        ->and(GalleryImage::published()->pluck('title')->all())->toBe(['One', 'Two']);
});

it('does not allow mass assigning is_main', function () {
    $branch = Branch::create(['name' => 'X', 'slug' => 'x', 'is_main' => true]);

    expect($branch->fresh()->is_main)->toBeFalse()
        ->and((new Service)->getFillable())->not->toBeEmpty()
        ->and((new Faq)->getFillable())->not->toBeEmpty()
        ->and((new GalleryImage)->getFillable())->not->toBeEmpty()
        ->and((new TeamMember)->getFillable())->not->toBeEmpty();
});
