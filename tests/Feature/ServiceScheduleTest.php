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
    return ServiceSchedule::build(UpcomingServices::forBranch($branch, $weeks, $now), $now, $branch);
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

it('checks the date window boundaries in runsOn', function () {
    $service = makeService($this->branch, 2, '19:00:00', 'Revival Night');
    $service->starts_on = '2026-12-01';
    $service->ends_on = '2026-12-15';

    expect($service->runsOn(CarbonImmutable::parse('2026-11-30 23:59')))->toBeFalse()
        ->and($service->runsOn(CarbonImmutable::parse('2026-12-01 00:00')))->toBeTrue()
        ->and($service->runsOn(CarbonImmutable::parse('2026-12-15 23:59')))->toBeTrue()
        ->and($service->runsOn(CarbonImmutable::parse('2026-12-16 00:00')))->toBeFalse();

    $open = makeService($this->branch, 3, '19:00:00', 'Open');
    expect($open->runsOn(CarbonImmutable::parse('2030-01-01')))->toBeTrue();
});

it('respects starts_on and ends_on in nextOccurrence', function () {
    $service = makeService($this->branch, 2, '19:00:00', 'Revival Night');
    $service->starts_on = '2027-01-05'; // a Tuesday
    $service->ends_on = '2027-01-12';

    expect($service->nextOccurrence($this->now)->format('Y-m-d H:i'))->toBe('2027-01-05 19:00')
        ->and($service->nextOccurrence(CarbonImmutable::parse('2027-01-06 10:00', 'Asia/Manila'))->format('Y-m-d'))->toBe('2027-01-12')
        ->and($service->nextOccurrence(CarbonImmutable::parse('2027-01-12 20:00', 'Asia/Manila')))->toBeNull();
});

it('excludes services past ends_on and includes ones after starts_on', function () {
    $ended = makeService($this->branch, 2, '19:00:00', 'Revival Night');
    $ended->update(['ends_on' => '2026-11-24']);
    $later = makeService($this->branch, 3, '19:00:00', 'Salvation Night');
    $later->update(['starts_on' => '2026-12-09']); // a Wednesday

    $schedule = scheduleFor($this->branch, $this->now);
    $dates = $schedule['soon']->concat($schedule['later'])
        ->flatMap(fn ($d) => $d['items']->map(fn ($i) => $i['service']->name.' '.$i['starts_at']->format('Y-m-d')))->all();

    expect($dates)->toBe(['Salvation Night 2026-12-09', 'Salvation Night 2026-12-16', 'Salvation Night 2026-12-23'])
        ->and($schedule['weekly']->pluck('name')->all())->toBe(['Salvation Night']);
});

it('adds weekly notes for resuming and last-service windows', function () {
    $later = makeService($this->branch, 3, '19:00:00', 'Salvation Night');
    $later->update(['starts_on' => '2026-12-09']);
    $ending = makeService($this->branch, 2, '19:00:00', 'Revival Night');
    $ending->update(['ends_on' => '2026-12-08']);
    makeService($this->branch, 0, '09:00:00', 'Sunday Service');

    $weekly = scheduleFor($this->branch, $this->now)['weekly']->keyBy('name');

    expect($weekly['Salvation Night']['note'])->toBe('Resumes Dec 9, 2026')
        ->and($weekly['Revival Night']['note'])->toBe('Last service Dec 8')
        ->and($weekly['Sunday Service'])->not->toHaveKey('note');
});

it('lists a paused service in weekly with a Resumes note', function () {
    makeService($this->branch, 2, '19:00:00', 'Revival Night')->update(['starts_on' => '2027-02-23']); // 3 months out

    $schedule = scheduleFor($this->branch, $this->now);

    expect($schedule['soon'])->toBeEmpty()
        ->and($schedule['weekly']->first()['name'])->toBe('Revival Night')
        ->and($schedule['weekly']->first()['note'])->toBe('Resumes Feb 23, 2027');
});

it('shows the last-service note only within 8 weeks', function () {
    makeService($this->branch, 2, '19:00:00', 'Revival Night')->update(['ends_on' => '2027-02-02']); // > 8 weeks
    makeService($this->branch, 3, '19:00:00', 'Salvation Night')->update(['ends_on' => '2026-12-23']); // within

    $weekly = scheduleFor($this->branch, $this->now)['weekly']->keyBy('name');

    expect($weekly['Revival Night'])->not->toHaveKey('note')
        ->and($weekly['Salvation Night']['note'])->toBe('Last service Dec 23');
});

it('keeps a service ending today in weekly after its start time has passed', function () {
    makeService($this->branch, 6, '08:00:00', 'Empowered Night')->update(['ends_on' => '2026-11-28']); // now is 10:00

    $schedule = scheduleFor($this->branch, $this->now);

    expect($schedule['soon'])->toBeEmpty()
        ->and($schedule['weekly']->first()['note'])->toBe('Last service Nov 28');
});

it('drops a service from weekly when its last occurrence before ends_on has passed', function () {
    // Tuesday service, ends Monday Nov 30; now is Saturday Nov 28, so its last Tuesday (Nov 24) is over.
    makeService($this->branch, 2, '19:00:00', 'Revival Night')->update(['ends_on' => '2026-11-30']);
    makeService($this->branch, 0, '09:00:00', 'Sunday Service');

    expect(scheduleFor($this->branch, $this->now)['weekly']->pluck('name')->all())->toBe(['Sunday Service']);
});

it('dates the last-service note on the final weekday, not on ends_on', function () {
    // Tuesday service, ends Thursday Dec 10: the last service is Tuesday Dec 8.
    makeService($this->branch, 2, '19:00:00', 'Revival Night')->update(['ends_on' => '2026-12-10']);

    expect(scheduleFor($this->branch, $this->now)['weekly']->first()['note'])->toBe('Last service Dec 8');
});

it('only links weekly times whose calendar file exists', function () {
    makeService($this->branch, 2, '19:00:00', 'Revival Night')->update(['ends_on' => '2026-11-30']);
    makeService($this->branch, 0, '09:00:00', 'Sunday Service');

    foreach (scheduleFor($this->branch, $this->now)['weekly']->pluck('calendar_urls')->flatten() as $url) {
        $this->get($url)->assertOk();
    }
});

it('lists per-time notes when services on one line differ', function () {
    makeService($this->branch, 0, '05:30:00', 'Sunday Service');
    makeService($this->branch, 0, '07:30:00', 'Sunday Service')->update(['ends_on' => '2026-12-13']);
    makeService($this->branch, 0, '09:30:00', 'Sunday Service')->update(['ends_on' => '2026-12-13']);

    $row = scheduleFor($this->branch, $this->now)['weekly']->first();

    expect($row)->not->toHaveKey('note')
        ->and($row['notes'])->toBe([
            ['time' => '7:30 AM', 'note' => 'Last service Dec 13'],
            ['time' => '9:30 AM', 'note' => 'Last service Dec 13'],
        ]);
});
