<?php

use App\Models\Branch;
use App\Models\Service;
use Carbon\CarbonImmutable;

beforeEach(function () {
    config(['app.timezone' => 'Asia/Manila']);
    // Saturday 2026-11-28 10:00 Manila.
    $this->travelTo(CarbonImmutable::parse('2026-11-28 10:00', 'Asia/Manila'));
    $this->branch = Branch::factory()->create(['name' => 'COG Dasma']);
});

function calService(Branch $branch, array $attrs = []): Service
{
    return Service::factory()->create(array_merge([
        'branch_id' => $branch->id,
        'name' => 'Revival Night',
        'day_of_week' => 5,
        'start_time' => '19:00:00',
    ], $attrs));
}

it('serves an ics file', function () {
    $service = calService($this->branch);

    $response = $this->get(route('services.calendar', $service));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
    expect($response->headers->get('Content-Disposition'))->toContain('revival-night-friday.ics');

    $body = $response->getContent();
    expect($body)->toContain("BEGIN:VEVENT\r\n")
        ->and($body)->toContain('RRULE:FREQ=WEEKLY;BYDAY=FR')
        ->and($body)->toContain('DTSTART;TZID=Asia/Manila:20261204T190000')
        ->and($body)->toContain('DTEND;TZID=Asia/Manila:20261204T203000')
        ->and($body)->toContain('LOCATION:COG Dasma')
        ->and($body)->not->toContain('UNTIL')
        ->and(preg_match('/(?<!\r)\n/', $body))->toBe(0);
});

it('starts a paused service on its starts_on week', function () {
    $service = calService($this->branch, ['starts_on' => '2027-01-10']);

    expect($this->get(route('services.calendar', $service))->getContent())
        ->toContain('DTSTART;TZID=Asia/Manila:20270115T190000');
});

it('adds UNTIL when ends_on is set', function () {
    $service = calService($this->branch, ['ends_on' => '2026-12-31']);

    expect($this->get(route('services.calendar', $service))->getContent())
        ->toContain('UNTIL=20261231T155959Z');
});

it('returns 404 for inactive, ended, or unknown services', function () {
    $inactive = calService($this->branch, ['is_active' => false]);
    $ended = calService($this->branch, ['ends_on' => '2026-11-01', 'day_of_week' => 2]);

    $this->get(route('services.calendar', $inactive))->assertNotFound();
    $this->get(route('services.calendar', $ended))->assertNotFound();
    $this->get('/services/99999/calendar.ics')->assertNotFound();
});

it('escapes text values', function () {
    $service = calService($this->branch, ['name' => 'Praise, Prayer; Word']);

    expect($this->get(route('services.calendar', $service))->getContent())
        ->toContain('SUMMARY:Praise\\, Prayer\\; Word');
});

it('folds long lines to 75 octets', function () {
    $service = calService($this->branch, ['name' => str_repeat('Long Name ', 20)]);

    $lines = explode("\r\n", $this->get(route('services.calendar', $service))->getContent());

    expect(collect($lines)->every(fn ($l) => strlen($l) <= 75))->toBeTrue();
});

it('renders calendar links on the homepage', function () {
    $this->branch->forceFill(['is_main' => true])->save();
    $service = calService($this->branch);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('services.calendar', $service), false);
});
