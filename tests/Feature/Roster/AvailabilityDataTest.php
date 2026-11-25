<?php

use App\Enums\AvailabilityPart;
use App\Models\Branch;
use App\Models\Event;
use App\Models\MemberUnavailability;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;

function eventAt(string $time): Event
{
    $start = now()->addWeek()->setTimeFromTimeString($time);

    return Event::factory()->create(['starts_at' => $start, 'ends_at' => $start->copy()->addHours(2)]);
}

test('event block is AM before the PM cutoff', function (string $time) {
    expect(eventAt($time)->block())->toBe(AvailabilityPart::Am);
})->with(['07:30', '09:30', '13:30', '14:59']);

test('event block is PM at or after the cutoff', function (string $time) {
    expect(eventAt($time)->block())->toBe(AvailabilityPart::Pm);
})->with(['15:00', '15:30', '19:00']);

test('event block reads the cutoff from config', function () {
    $event = eventAt('13:30');
    expect($event->block())->toBe(AvailabilityPart::Am);

    config(['ministry.duty_pm_starts_at' => '13:00']);
    expect($event->block())->toBe(AvailabilityPart::Pm);
});

test('availability part has labels', function () {
    expect(AvailabilityPart::WholeDay->label())->toBe('Whole day')
        ->and(AvailabilityPart::Am->label())->toBe('AM')
        ->and(AvailabilityPart::Pm->label())->toBe('PM');
});

test('unavailability casts and relations work', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);

    $entry = MemberUnavailability::create([
        'user_id' => $user->id,
        'branch_id' => $branch->id,
        'date' => now()->addDays(3)->toDateString(),
        'part' => 'am',
        'note' => 'Exam',
    ]);

    expect($entry->part)->toBe(AvailabilityPart::Am)
        ->and($entry->date)->toBeInstanceOf(CarbonInterface::class)
        ->and($entry->user->is($user))->toBeTrue()
        ->and($entry->branch->is($branch))->toBeTrue()
        ->and($user->unavailabilities)->toHaveCount(1);
});

test('upcoming scope excludes past dates and includes today', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $make = fn ($d) => MemberUnavailability::create([
        'user_id' => $user->id, 'branch_id' => $branch->id,
        'date' => $d->toDateString(), 'part' => AvailabilityPart::WholeDay,
    ]);
    $make(now()->subDay());
    $today = $make(now());
    $future = $make(now()->addDay());

    expect(MemberUnavailability::upcoming()->pluck('id')->all())
        ->toEqualCanonicalizing([$today->id, $future->id]);
});

test('one entry per user per date', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $attrs = ['user_id' => $user->id, 'branch_id' => $branch->id, 'date' => now()->addDay()->toDateString(), 'part' => 'pm'];
    MemberUnavailability::create($attrs);

    expect(fn () => MemberUnavailability::create($attrs))->toThrow(QueryException::class);
});
