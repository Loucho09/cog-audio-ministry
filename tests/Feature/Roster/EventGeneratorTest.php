<?php

use App\Exceptions\RosterRuleViolation;
use App\Models\Branch;
use App\Models\DutyRole;
use App\Models\Event;
use App\Models\Service;
use App\Services\EventGenerator;
use Carbon\CarbonImmutable;

function sundayService(Branch $branch, array $attrs = []): Service
{
    return Service::factory()->create(array_merge([
        'branch_id' => $branch->id,
        'day_of_week' => 0,
        'start_time' => '09:00:00',
    ], $attrs));
}

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-12-01 08:00', 'Asia/Manila'));
});

function genFrom(): CarbonImmutable
{
    return CarbonImmutable::parse('2026-12-06', 'Asia/Manila'); // Sunday
}

it('generates one event per weekly occurrence with copied slots', function () {
    $branch = Branch::factory()->create();
    $service = sundayService($branch, ['name' => 'Sunday Worship']);
    $role = DutyRole::factory()->create(['branch_id' => $branch->id]);
    $service->dutySlots()->create(['duty_role_id' => $role->id, 'quantity' => 2]);

    $count = app(EventGenerator::class)->generate($branch, genFrom(), genFrom()->addDays(21));

    expect($count)->toBe(4);
    $events = Event::where('service_id', $service->id)->orderBy('starts_at')->get();
    expect($events)->toHaveCount(4);
    expect($events[0]->name)->toBe('Sunday Worship');
    expect($events[0]->starts_at->format('Y-m-d H:i'))->toBe('2026-12-06 09:00');
    expect($events[0]->ends_at->format('Y-m-d H:i'))->toBe('2026-12-06 11:00');
    expect($events[0]->branch_id)->toBe($branch->id);
    expect($events[0]->dutySlots)->toHaveCount(1);
    expect($events[0]->dutySlots->first()->quantity)->toBe(2);
});

it('running twice creates no duplicates', function () {
    $branch = Branch::factory()->create();
    sundayService($branch);
    $gen = app(EventGenerator::class);

    expect($gen->generate($branch, genFrom(), genFrom()->addDays(14)))->toBe(3);
    expect($gen->generate($branch, genFrom(), genFrom()->addDays(14)))->toBe(0);
    expect(Event::count())->toBe(3);
});

it('changed service time does not duplicate a date', function () {
    $branch = Branch::factory()->create();
    $service = sundayService($branch);
    $gen = app(EventGenerator::class);
    $gen->generate($branch, genFrom(), genFrom());

    $service->update(['start_time' => '10:30:00']);

    expect($gen->generate($branch, genFrom(), genFrom()))->toBe(0);
    expect(Event::count())->toBe(1);
});

it('respects the service date window', function () {
    $branch = Branch::factory()->create();
    sundayService($branch, ['starts_on' => '2026-12-13', 'ends_on' => '2026-12-20']);

    $count = app(EventGenerator::class)->generate($branch, genFrom(), genFrom()->addDays(21));

    expect($count)->toBe(2);
    expect(Event::orderBy('starts_at')->pluck('starts_at')->map->format('Y-m-d')->all())
        ->toBe(['2026-12-13', '2026-12-20']);
});

it('ignores inactive services and other branches', function () {
    $branch = Branch::factory()->create();
    $other = Branch::factory()->create();
    sundayService($branch, ['is_active' => false]);
    sundayService($other);

    expect(app(EventGenerator::class)->generate($branch, genFrom(), genFrom()->addDays(14)))->toBe(0);
    expect(Event::count())->toBe(0);
});

it('rejects ranges over 3 months and backwards ranges', function () {
    $branch = Branch::factory()->create();
    $gen = app(EventGenerator::class);

    expect(fn () => $gen->generate($branch, genFrom(), genFrom()->addMonths(3)->addDay()))
        ->toThrow(RosterRuleViolation::class);
    expect(fn () => $gen->generate($branch, genFrom(), genFrom()->subDay()))
        ->toThrow(RosterRuleViolation::class);
    expect(fn () => $gen->generate($branch, genFrom(), genFrom()->addMonths(3)))
        ->not->toThrow(RosterRuleViolation::class);
});

it('skips an occurrence that has already started today', function () {
    $branch = Branch::factory()->create();
    sundayService($branch, ['start_time' => '09:00:00']);
    $this->travelTo(CarbonImmutable::parse('2026-12-06 10:00', 'Asia/Manila'));

    expect(app(EventGenerator::class)->generate($branch, genFrom(), genFrom()))->toBe(0);

    $this->travelTo(CarbonImmutable::parse('2026-12-06 08:00', 'Asia/Manila'));

    expect(app(EventGenerator::class)->generate($branch, genFrom(), genFrom()))->toBe(1);
});
