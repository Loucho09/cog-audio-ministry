<?php

use App\Enums\AvailabilityPart;
use App\Enums\DutyStatus;
use App\Exceptions\RosterRuleViolation;
use App\Models\DutyAssignment;
use App\Models\MemberUnavailability;
use App\Models\User;
use App\Services\AvailabilityService;

function avail(): AvailabilityService
{
    return app(AvailabilityService::class);
}

function dutyFor(User $member, string $time, DutyStatus $status = DutyStatus::Accepted): string
{
    [$event, $role] = rosterEvent($time, '17:00');
    DutyAssignment::factory()->create([
        'event_id' => $event->id, 'duty_role_id' => $role->id, 'user_id' => $member->id,
        'branch_id' => $event->branch_id, 'status' => $status,
    ]);

    return $event->starts_at->toDateString();
}

function availMember(): User
{
    return rosterMember(rosterEvent()[0]);
}

test('marking today works and uses the user branch', function () {
    $user = availMember();

    $entry = avail()->mark($user, now(), AvailabilityPart::Am, null);

    expect($entry->branch_id)->toBe($user->branch_id)
        ->and($entry->user_id)->toBe($user->id)
        ->and($entry->part)->toBe(AvailabilityPart::Am);
});

test('yesterday is rejected', function () {
    expect(fn () => avail()->mark(availMember(), now()->subDay(), AvailabilityPart::Am, null))
        ->toThrow(RosterRuleViolation::class);
});

test('six months ahead is allowed, beyond is rejected', function () {
    $user = availMember();

    expect(avail()->mark($user, now()->addMonths(6), AvailabilityPart::Pm, null))->toBeInstanceOf(MemberUnavailability::class);
    expect(fn () => avail()->mark($user, now()->addMonths(6)->addDay(), AvailabilityPart::Pm, null))
        ->toThrow(RosterRuleViolation::class);
});

test('accepts a date string', function () {
    $entry = avail()->mark(availMember(), now()->addDays(2)->toDateString(), AvailabilityPart::WholeDay, null);

    expect($entry->date->toDateString())->toBe(now()->addDays(2)->toDateString());
});

test('duplicate date is rejected', function () {
    $user = availMember();
    avail()->mark($user, now()->addDay(), AvailabilityPart::Am, null);

    expect(fn () => avail()->mark($user, now()->addDay(), AvailabilityPart::Pm, null))
        ->toThrow(RosterRuleViolation::class, 'You already marked this date. Remove it first to change it.');
});

test('AM mark is blocked by a 9:30 duty on that date', function () {
    $user = availMember();
    $date = dutyFor($user, '09:30');

    expect(fn () => avail()->mark($user, $date, AvailabilityPart::Am, null))
        ->toThrow(RosterRuleViolation::class, "You're on duty then. Offer a swap or ask your admin.");
});

test('AM mark is not blocked by a 15:30 duty', function () {
    $user = availMember();
    $date = dutyFor($user, '15:30');

    expect(avail()->mark($user, $date, AvailabilityPart::Am, null))->toBeInstanceOf(MemberUnavailability::class);
});

test('PM mark is blocked by a 15:30 duty but not a 9:30 one', function () {
    $pmUser = availMember();
    $date = dutyFor($pmUser, '15:30');
    expect(fn () => avail()->mark($pmUser, $date, AvailabilityPart::Pm, null))->toThrow(RosterRuleViolation::class);

    $amUser = availMember();
    $date = dutyFor($amUser, '09:30');
    expect(avail()->mark($amUser, $date, AvailabilityPart::Pm, null))->toBeInstanceOf(MemberUnavailability::class);
});

test('whole day is blocked by either an AM or a PM duty', function (string $time) {
    $user = availMember();
    $date = dutyFor($user, $time);

    expect(fn () => avail()->mark($user, $date, AvailabilityPart::WholeDay, null))
        ->toThrow(RosterRuleViolation::class, "You're on duty");
})->with(['09:30', '15:30']);

test('assigned (not yet accepted) duty also blocks', function () {
    $user = availMember();
    $date = dutyFor($user, '09:30', DutyStatus::Assigned);

    expect(fn () => avail()->mark($user, $date, AvailabilityPart::Am, null))->toThrow(RosterRuleViolation::class);
});

test('declined duty does not block', function () {
    $user = availMember();
    $date = dutyFor($user, '09:30', DutyStatus::Declined);

    expect(avail()->mark($user, $date, AvailabilityPart::WholeDay, null))->toBeInstanceOf(MemberUnavailability::class);
});

test('duty on another date does not block', function () {
    $user = availMember();
    dutyFor($user, '09:30');

    expect(avail()->mark($user, now()->addDays(2), AvailabilityPart::WholeDay, null))->toBeInstanceOf(MemberUnavailability::class);
});

test('another member duty does not block', function () {
    $other = availMember();
    $date = dutyFor($other, '09:30');

    expect(avail()->mark(availMember(), $date, AvailabilityPart::WholeDay, null))->toBeInstanceOf(MemberUnavailability::class);
});

test('note is trimmed, blank becomes null, over 100 is rejected', function () {
    $user = availMember();

    $a = avail()->mark($user, now()->addDay(), AvailabilityPart::Am, '  Exam week  ');
    $b = avail()->mark($user, now()->addDays(2), AvailabilityPart::Am, "   \n ");
    $c = avail()->mark($user, now()->addDays(3), AvailabilityPart::Am, str_repeat('a', 100));

    expect($a->note)->toBe('Exam week')->and($b->note)->toBeNull()->and($c->note)->toHaveLength(100);
    expect(fn () => avail()->mark($user, now()->addDays(4), AvailabilityPart::Am, str_repeat('a', 101)))
        ->toThrow(RosterRuleViolation::class);
});

test('remove deletes a future or today entry', function () {
    $user = availMember();
    $entry = avail()->mark($user, now(), AvailabilityPart::Am, null);

    avail()->remove($entry);

    expect(MemberUnavailability::count())->toBe(0);
});

test('remove rejects past dates', function () {
    $user = availMember();
    $entry = MemberUnavailability::create([
        'user_id' => $user->id, 'branch_id' => $user->branch_id,
        'date' => now()->subDay()->toDateString(), 'part' => AvailabilityPart::Am,
    ]);

    expect(fn () => avail()->remove($entry))->toThrow(RosterRuleViolation::class, "Past dates can't be changed.");
    expect(MemberUnavailability::count())->toBe(1);
});

test('policy: owner can delete, another member cannot', function () {
    $owner = availMember();
    $other = availMember();
    $entry = avail()->mark($owner, now()->addDay(), AvailabilityPart::Am, null);

    expect($owner->can('delete', $entry))->toBeTrue()
        ->and($other->can('delete', $entry))->toBeFalse();
});

test('policy: members can create, admins and others cannot', function () {
    $member = availMember();
    $admin = User::factory()->branchAdmin($member->branch)->create();
    $roleless = User::factory()->create();

    expect($member->can('create', MemberUnavailability::class))->toBeTrue()
        ->and($admin->can('create', MemberUnavailability::class))->toBeFalse()
        ->and($roleless->can('create', MemberUnavailability::class))->toBeFalse();
});

test('a user with no branch cannot mark, and nothing is inserted', function () {
    $user = User::factory()->create(['branch_id' => null]);

    expect(fn () => avail()->mark($user, now()->addDay(), AvailabilityPart::Am, null))
        ->toThrow(RosterRuleViolation::class, 'Your account has no branch yet. Please ask your admin.');
    expect(MemberUnavailability::count())->toBe(0);
});

test('six month boundary: the day before is ok, the day after is rejected', function () {
    $user = availMember();
    $limit = now()->startOfDay()->addMonths(6);

    expect(avail()->mark($user, $limit->copy()->subDay(), AvailabilityPart::Am, null))->toBeInstanceOf(MemberUnavailability::class);
    expect(fn () => avail()->mark($user, $limit->copy()->addDay(), AvailabilityPart::Am, null))
        ->toThrow(RosterRuleViolation::class);
});

test('marking is allowed when today duty already started (duties only block before the event starts)', function () {
    $user = availMember();
    [$event, $role] = rosterEvent('09:00', '11:00');
    $event->update(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
    DutyAssignment::factory()->create([
        'event_id' => $event->id, 'duty_role_id' => $role->id, 'user_id' => $user->id,
        'branch_id' => $event->branch_id, 'status' => DutyStatus::Accepted,
    ]);

    expect(avail()->mark($user, now(), AvailabilityPart::WholeDay, null))->toBeInstanceOf(MemberUnavailability::class);
});

test('on-duty check uses the app timezone date, not UTC', function () {
    $user = availMember();
    // 06:00 Manila is still the previous day in UTC.
    [$event] = rosterEvent('06:00', '08:00');
    DutyAssignment::factory()->create([
        'event_id' => $event->id, 'duty_role_id' => $event->dutySlots()->first()->duty_role_id, 'user_id' => $user->id,
        'branch_id' => $event->branch_id, 'status' => DutyStatus::Accepted,
    ]);
    $localDate = $event->starts_at->copy()->setTimezone(config('app.timezone'))->toDateString();
    expect($event->starts_at->copy()->utc()->toDateString())->not->toBe($localDate);

    expect(fn () => avail()->mark($user, $localDate, AvailabilityPart::Am, null))
        ->toThrow(RosterRuleViolation::class, "You're on duty");

    $utcDate = $event->starts_at->copy()->utc()->toDateString();
    expect(avail()->mark($user, $utcDate, AvailabilityPart::Am, null))->toBeInstanceOf(MemberUnavailability::class);
});
