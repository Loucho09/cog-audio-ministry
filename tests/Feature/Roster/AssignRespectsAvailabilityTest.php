<?php

use App\Enums\AvailabilityPart;
use App\Exceptions\RosterRuleViolation;
use App\Models\DutyAssignment;
use App\Models\MemberUnavailability;

function markOff($user, $event, AvailabilityPart $part, ?string $date = null): MemberUnavailability
{
    return MemberUnavailability::create([
        'user_id' => $user->id,
        'branch_id' => $user->branch_id,
        'date' => $date ?? $event->starts_at->toDateString(),
        'part' => $part,
    ]);
}

test('AM entry blocks a 9:30 event but not a 15:30 event on the same date', function () {
    [$am, $role] = rosterEvent('09:30', '11:00');
    [$pm] = rosterEvent('15:30', '17:00', 1, $role, $am);
    $member = rosterMember($am);
    markOff($member, $am, AvailabilityPart::Am);

    expect(fn () => svc()->assign($am, $role, $member))
        ->toThrow(RosterRuleViolation::class, "{$member->name} marked this time as unavailable.");

    expect(svc()->assign($pm, $role, $member))->toBeInstanceOf(DutyAssignment::class);
});

test('PM entry blocks a 15:30 event but not a 9:30 event', function () {
    [$am, $role] = rosterEvent('09:30', '11:00');
    [$pm] = rosterEvent('15:30', '17:00', 1, $role, $am);
    $member = rosterMember($am);
    markOff($member, $am, AvailabilityPart::Pm);

    expect(fn () => svc()->assign($pm, $role, $member))
        ->toThrow(RosterRuleViolation::class, 'marked this time as unavailable.');

    expect(svc()->assign($am, $role, $member))->toBeInstanceOf(DutyAssignment::class);
});

test('whole day blocks both events', function () {
    [$am, $role] = rosterEvent('09:30', '11:00');
    [$pm] = rosterEvent('15:30', '17:00', 1, $role, $am);
    $member = rosterMember($am);
    markOff($member, $am, AvailabilityPart::WholeDay);

    expect(fn () => svc()->assign($am, $role, $member))->toThrow(RosterRuleViolation::class);
    expect(fn () => svc()->assign($pm, $role, $member))->toThrow(RosterRuleViolation::class);
});

test('an entry on another date does not block', function () {
    [$event, $role] = rosterEvent('09:30', '11:00');
    $member = rosterMember($event);
    markOff($member, $event, AvailabilityPart::WholeDay, $event->starts_at->copy()->addDay()->toDateString());

    expect(svc()->assign($event, $role, $member))->toBeInstanceOf(DutyAssignment::class);
});

test('another member entry does not block', function () {
    [$event, $role] = rosterEvent('09:30', '11:00');
    $member = rosterMember($event);
    $other = rosterMember($event);
    markOff($other, $event, AvailabilityPart::WholeDay);

    expect(svc()->assign($event, $role, $member))->toBeInstanceOf(DutyAssignment::class);
});

test('guest assignment is unaffected by availability', function () {
    [$event, $role] = rosterEvent('09:30', '11:00');
    $member = rosterMember($event);
    markOff($member, $event, AvailabilityPart::WholeDay);

    expect(svc()->assignGuest($event, $role, $member->name))->toBeInstanceOf(DutyAssignment::class);
});

test('blocks() helper matches date and block', function () {
    [$am, $role] = rosterEvent('09:30', '11:00');
    [$pm] = rosterEvent('15:30', '17:00', 1, $role, $am);
    $member = rosterMember($am);
    $other = rosterMember($am);

    expect(MemberUnavailability::blocks($member, $am))->toBeFalse();

    markOff($member, $am, AvailabilityPart::Am);

    expect(MemberUnavailability::blocks($member, $am))->toBeTrue()
        ->and(MemberUnavailability::blocks($member, $pm))->toBeFalse()
        ->and(MemberUnavailability::blocks($other, $am))->toBeFalse();
});

test('blocks() treats whole day as blocking both parts', function () {
    [$am, $role] = rosterEvent('09:30', '11:00');
    [$pm] = rosterEvent('15:30', '17:00', 1, $role, $am);
    $member = rosterMember($am);
    markOff($member, $am, AvailabilityPart::WholeDay);

    expect(MemberUnavailability::blocks($member, $am))->toBeTrue()
        ->and(MemberUnavailability::blocks($member, $pm))->toBeTrue();
});

test('blocks() uses the app timezone date when the UTC date differs', function () {
    [$event] = rosterEvent('06:00', '08:00');
    $member = rosterMember($event);
    $localDate = $event->starts_at->copy()->setTimezone(config('app.timezone'))->toDateString();
    $utcDate = $event->starts_at->copy()->utc()->toDateString();
    expect($utcDate)->not->toBe($localDate);

    markOff($member, $event, AvailabilityPart::Am, $utcDate);
    expect(MemberUnavailability::blocks($member, $event))->toBeFalse();

    markOff($member, $event, AvailabilityPart::Am, $localDate);
    expect(MemberUnavailability::blocks($member, $event))->toBeTrue();
});

test('blocks() follows a changed config cutoff', function () {
    [$event] = rosterEvent('10:30', '12:00');
    $member = rosterMember($event);
    markOff($member, $event, AvailabilityPart::Am);

    expect(MemberUnavailability::blocks($member, $event))->toBeTrue();

    config(['ministry.duty_pm_starts_at' => '10:00']);

    expect(MemberUnavailability::blocks($member, $event))->toBeFalse();
});
