<?php

use App\Actions\UpcomingServices;
use App\Models\Branch;
use App\Models\Faq;
use App\Models\GalleryImage;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\ImageUrl;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

function manilaTime(string $datetime): CarbonImmutable
{
    return CarbonImmutable::parse($datetime, 'Asia/Manila');
}

it('runs the seeders', function () {
    $dir = public_path('images/gallery');
    $real = collect(File::isDirectory($dir) ? File::files($dir) : [])
        ->filter(fn ($f) => in_array(strtolower($f->getExtension()), ['jpg', 'jpeg', 'png', 'webp'], true))
        ->count();
    $expectedGallery = $real === 0 ? 6 : $real;

    $this->seed(DatabaseSeeder::class);

    expect(Branch::count())->toBe(5)
        ->and(Branch::main()->count())->toBe(1)
        ->and(Branch::mainBranch()->slug)->toBe('dasma')
        ->and(Service::count())->toBe(10)
        ->and(Service::where('branch_id', Branch::mainBranch()->id)->count())->toBe(10)
        ->and(Service::where('name', 'Sunday Service')->count())->toBe(7)
        ->and(Faq::published()->count())->toBeGreaterThanOrEqual(4)
        ->and(GalleryImage::published()->count())->toBe($expectedGallery)
        ->and(TeamMember::count())->toBe(0);
});

it('keeps admin edits when seeders re-run and is idempotent', function () {
    $this->seed(DatabaseSeeder::class);
    Faq::where('question', 'How do I apply?')->update(['answer' => 'Edited']);
    $this->seed(DatabaseSeeder::class);

    expect(Faq::where('question', 'How do I apply?')->value('answer'))->toBe('Edited')
        ->and(Service::count())->toBe(10)
        ->and(User::where('email', 'test@example.com')->count())->toBe(1);
});

it('keeps seeded FAQs out of the homepage', function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);

    $this->get('/')->assertOk()->assertDontSee('id="faq"', false);
});

it('uses the Manila timezone by default', function () {
    expect(config('app.timezone'))->toBe('Asia/Manila');
});

it('computes nextOccurrence later today', function () {
    $service = Service::factory()->make(['day_of_week' => 3, 'start_time' => '19:00:00']);
    $next = $service->nextOccurrence(manilaTime('2026-11-25 10:00')); // Wednesday

    expect($next->format('Y-m-d H:i'))->toBe('2026-11-25 19:00');
});

it('rolls nextOccurrence to next week when today has passed', function () {
    $service = Service::factory()->make(['day_of_week' => 3, 'start_time' => '19:00:00']);
    $next = $service->nextOccurrence(manilaTime('2026-11-25 20:00'));

    expect($next->format('Y-m-d H:i'))->toBe('2026-12-02 19:00');
});

it('computes nextOccurrence for a different weekday', function () {
    $service = Service::factory()->make(['day_of_week' => 0, 'start_time' => '05:30:00']);
    $next = $service->nextOccurrence(manilaTime('2026-11-25 10:00')); // Wed -> Sun

    expect($next->format('Y-m-d H:i'))->toBe('2026-11-29 05:30');
});

it('returns the same instant when now is exactly the start time', function () {
    $service = Service::factory()->make(['day_of_week' => 3, 'start_time' => '19:00:00']);

    expect($service->nextOccurrence(manilaTime('2026-11-25 19:00:00'))->format('Y-m-d H:i:s'))->toBe('2026-11-25 19:00:00');
});

it('rolls one week when one second after the start', function () {
    $service = Service::factory()->make(['day_of_week' => 3, 'start_time' => '19:00:00']);

    expect($service->nextOccurrence(manilaTime('2026-11-25 19:00:01'))->format('Y-m-d H:i'))->toBe('2026-12-02 19:00');
});

it('finds Sunday 05:30 from Saturday night across the week boundary', function () {
    $service = Service::factory()->make(['day_of_week' => 0, 'start_time' => '05:30:00']);

    expect($service->nextOccurrence(manilaTime('2026-11-28 23:00'))->format('Y-m-d H:i'))->toBe('2026-11-29 05:30');
});

it('converts a UTC from to Manila', function () {
    $service = Service::factory()->make(['day_of_week' => 3, 'start_time' => '19:00:00']);
    $next = $service->nextOccurrence(CarbonImmutable::parse('2026-11-25 12:00', 'UTC')); // 20:00 Manila

    expect($next->format('Y-m-d H:i'))->toBe('2026-12-02 19:00')
        ->and($next->timezoneName)->toBe('Asia/Manila');
});

it('accepts start_time stored as HH:MM', function () {
    $service = Service::factory()->make(['day_of_week' => 3, 'start_time' => '19:00']);

    expect($service->nextOccurrence(manilaTime('2026-11-25 10:00'))->format('Y-m-d H:i'))->toBe('2026-11-25 19:00');
});

it('uses the current time when from is null', function () {
    $this->travelTo(manilaTime('2026-11-25 10:00'));
    $service = Service::factory()->make(['day_of_week' => 3, 'start_time' => '19:00:00']);

    expect($service->nextOccurrence()->format('Y-m-d H:i'))->toBe('2026-11-25 19:00');
});

it('throws a clear exception for a missing start_time', function () {
    $service = Service::factory()->make(['start_time' => null]);

    expect(fn () => $service->nextOccurrence(manilaTime('2026-11-25 10:00')))
        ->toThrow(InvalidArgumentException::class, 'start_time');
});

it('exposes startTimeParts', function () {
    expect(Service::factory()->make(['start_time' => '05:30:00'])->startTimeParts())->toBe([5, 30])
        ->and(Service::factory()->make(['start_time' => '17:30'])->startTimeParts())->toBe([17, 30]);
});

it('lists upcoming services sorted over 4 weeks and skips inactive ones', function () {
    $branch = Branch::factory()->create();
    Service::factory()->for($branch)->create(['name' => 'Wed', 'day_of_week' => 3, 'start_time' => '19:00:00']);
    Service::factory()->for($branch)->create(['name' => 'Sun', 'day_of_week' => 0, 'start_time' => '05:30:00']);
    Service::factory()->for($branch)->create(['name' => 'Off', 'day_of_week' => 2, 'is_active' => false]);
    Service::factory()->create(['name' => 'Other branch', 'day_of_week' => 1]);

    $items = UpcomingServices::forBranch($branch, 4, manilaTime('2026-11-25 10:00'));

    expect($items)->toHaveCount(8)
        ->and($items->pluck('service.name')->unique()->sort()->values()->all())->toBe(['Sun', 'Wed'])
        ->and($items->first()['service']->name)->toBe('Wed')
        ->and($items->first()['starts_at']->format('Y-m-d H:i'))->toBe('2026-11-25 19:00');

    $times = $items->pluck('starts_at')->map->getTimestamp()->all();
    $sorted = $times;
    sort($sorted);
    expect($times)->toBe($sorted);
});

it('orders services deterministically', function () {
    $branch = Branch::factory()->create();
    $b = Service::factory()->for($branch)->create(['day_of_week' => 1, 'sort_order' => 1]);
    $a = Service::factory()->for($branch)->create(['day_of_week' => 2, 'sort_order' => 1]);
    $c = Service::factory()->for($branch)->create(['day_of_week' => 3, 'sort_order' => 0]);

    expect($branch->services()->ordered()->pluck('id')->all())->toBe([$c->id, $b->id, $a->id]);
});

it('exposes branch content relations', function () {
    $branch = Branch::factory()->create();
    Service::factory()->for($branch)->create(['day_of_week' => 1, 'is_active' => true]);
    Service::factory()->for($branch)->create(['day_of_week' => 2, 'is_active' => false]);
    GalleryImage::factory()->for($branch)->create();
    TeamMember::factory()->for($branch)->create();

    expect($branch->activeServices)->toHaveCount(1)
        ->and($branch->galleryImages)->toHaveCount(1)
        ->and($branch->teamMembers)->toHaveCount(1);
});

it('rejects duplicate service slots per branch', function () {
    $branch = Branch::factory()->create();
    Service::factory()->for($branch)->create(['day_of_week' => 1, 'start_time' => '10:00:00']);

    expect(fn () => Service::factory()->for($branch)->create(['day_of_week' => 1, 'start_time' => '10:00:00']))
        ->toThrow(QueryException::class);
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

it('resolves image urls for bundled and storage paths', function () {
    expect(ImageUrl::for(null))->toBeNull()
        ->and(ImageUrl::for(''))->toBeNull()
        ->and(ImageUrl::for('images/logo.png'))->toBe(asset('images/logo.png'))
        ->and(ImageUrl::for('gallery/a.jpg'))->toBe(Storage::disk('public')->url('gallery/a.jpg'));

    $bundled = GalleryImage::factory()->make(['path' => 'images/placeholders/gallery-1.svg']);
    $upload = GalleryImage::factory()->make(['path' => 'gallery-uploads/x.jpg']);
    $member = TeamMember::factory()->make(['photo_path' => 'team/x.jpg']);
    $none = TeamMember::factory()->make(['photo_path' => null]);

    expect($bundled->url)->toBe(asset('images/placeholders/gallery-1.svg'))
        ->and($upload->url)->toBe(Storage::disk('public')->url('gallery-uploads/x.jpg'))
        ->and($member->photo_url)->toBe(Storage::disk('public')->url('team/x.jpg'))
        ->and($none->photo_url)->toBeNull();
});

it('does not allow mass assigning is_main', function () {
    $branch = Branch::create(['name' => 'X', 'slug' => 'x', 'is_main' => true]);

    expect($branch->fresh()->is_main)->toBeFalse();
});

it('allows mass assigning branch_id on Service', function () {
    expect((new Service)->isFillable('branch_id'))->toBeTrue();
});
