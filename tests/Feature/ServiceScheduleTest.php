<?php

use App\Actions\UpcomingServices;
use App\Models\Branch;
use App\Models\Service;
use App\Support\ServiceSchedule;
use Carbon\CarbonImmutable;

beforeEach(function () {
    config(['app.timezone' => 'Asia/Manila']);
    // Saturday 2026-11-28 10:00 Manila.
    $this->now = CarbonImmutable::parse('2026-11-28 10:00', 'Asia/Manila');
    $this->travelTo($this->now);
    $this->branch = Branch::factory()->create();
});

function scheduleFor(Branch $branch, CarbonImmutable $now, int $weeks = 4): array
{
    return ServiceSchedule::build(UpcomingServices::forBranch($branch, $weeks, $now), $now);
}

function makeService(Branch $branch, int $day, string $time, string $name, bool $active = true): Service
{
    return Service::factory()->create([
        'branch_id' => $branch->id,
        'day_of_week' => $day,
        'start_time' => $time,
        'name' => $name,
        'is_active' => $active,
    ]);
}

it('groups occurrences by calendar date in order', function () {
    makeService($this->branch, 0, '05:30:00', 'Sunday Service');
    makeService($this->branch, 0, '07:30:00', 'Sunday Service');
    makeService($this->branch, 6, '17:30:00', 'Empowered Night');

    $soon = scheduleFor($this->branch, $this->now)['soon'];

    expect($soon->first()['date']->format('Y-m-d'))->toBe('2026-11-28')
        ->and($soon->first()['items'])->toHaveCount(1)
        ->and($soon[1]['date']->format('Y-m-d'))->toBe('2026-11-29')
        ->and($soon[1]['items']->map(fn ($i) => $i['starts_at']->format('g:i A'))->all())->toBe(['5:30 AM', '7:30 AM']);
});

it('splits at the 7-day cutoff', function () {
    makeService($this->branch, 5, '19:00:00', 'Friday Prayer'); // Dec 4 = 6 days out
    makeService($this->branch, 6, '19:00:00', 'Saturday Night'); // Nov 28 and Dec 5 (exactly 7 days out)

    $schedule = scheduleFor($this->branch, $this->now);
    $soonDates = $schedule['soon']->map(fn ($d) => $d['date']->format('Y-m-d'))->all();
    $laterDates = $schedule['later']->map(fn ($d) => $d['date']->format('Y-m-d'))->all();

    expect($soonDates)->toContain('2026-12-04')->not->toContain('2026-12-05')
        ->and($laterDates)->toContain('2026-12-05')->not->toContain('2026-12-04');
});

it('badges only today and tomorrow', function () {
    makeService($this->branch, 6, '17:30:00', 'Saturday Night');
    makeService($this->branch, 0, '09:00:00', 'Sunday Service');
    makeService($this->branch, 2, '19:00:00', 'Revival Night');

    $soon = scheduleFor($this->branch, $this->now)['soon'];

    expect($soon->pluck('badge')->all())->toBe(['Today', 'Tomorrow', null]);
});

it('builds weekly summary lines with the weekday and ordered times', function () {
    makeService($this->branch, 0, '07:30:00', 'Sunday Service');
    makeService($this->branch, 0, '05:30:00', 'Sunday Service');
    makeService($this->branch, 2, '19:00:00', 'Revival Night');

    $lines = scheduleFor($this->branch, $this->now)['weekly']->pluck('text')->all();

    expect($lines)->toBe([
        'Sunday: Sunday Service, 5:30 AM, 7:30 AM',
        'Tuesday: Revival Night, 7:00 PM',
    ]);
});

it('excludes inactive services', function () {
    makeService($this->branch, 0, '09:00:00', 'Sunday Service');
    makeService($this->branch, 2, '19:00:00', 'Hidden Night', false);

    $schedule = scheduleFor($this->branch, $this->now);

    expect($schedule['weekly']->pluck('name')->all())->toBe(['Sunday Service'])
        ->and($schedule['soon']->pluck('items')->flatten(1)->pluck('service.name')->all())->not->toContain('Hidden Night');
});
