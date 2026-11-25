<?php

use App\Enums\DutyStatus;
use App\Exceptions\RosterRuleViolation;
use App\Models\User;

test('member can accept own assigned duty', function () {
    [$event, $role] = rosterEvent();
    $member = rosterMember($event);
    $a = svc()->assign($event, $role, $member);

    $a = svc()->respond($a, $member, DutyStatus::Accepted);

    expect($a->status)->toBe(DutyStatus::Accepted)
        ->and($a->responded_at)->not->toBeNull();
});

test('member can decline own assigned duty', function () {
    [$event, $role] = rosterEvent();
    $member = rosterMember($event);
    $a = svc()->assign($event, $role, $member);

    $a = svc()->respond($a, $member, DutyStatus::Declined);

    expect($a->fresh()->status)->toBe(DutyStatus::Declined)
        ->and($a->responded_at)->not->toBeNull();
});

test('cannot respond to someone else\'s duty', function () {
    [$event, $role] = rosterEvent();
    $a = svc()->assign($event, $role, rosterMember($event));
    $other = User::factory()->member($event->branch)->create();

    expect(fn () => svc()->respond($a, $other, DutyStatus::Accepted))
        ->toThrow(RosterRuleViolation::class, 'not yours');
});

test('cannot respond twice', function () {
    [$event, $role] = rosterEvent();
    $member = rosterMember($event);
    $a = svc()->assign($event, $role, $member);
    svc()->respond($a, $member, DutyStatus::Accepted);

    expect(fn () => svc()->respond($a, $member, DutyStatus::Declined))
        ->toThrow(RosterRuleViolation::class, 'already responded');
    expect($a->fresh()->status)->toBe(DutyStatus::Accepted);
});

test('cannot respond after the event started', function () {
    [$event, $role] = rosterEvent();
    $member = rosterMember($event);
    $a = svc()->assign($event, $role, $member);
    $this->travelTo($event->starts_at->copy()->addMinutes(5));

    expect(fn () => svc()->respond($a, $member, DutyStatus::Accepted))
        ->toThrow(RosterRuleViolation::class, 'already started');
});

test('respond answer must be accepted or declined', function () {
    [$event, $role] = rosterEvent();
    $member = rosterMember($event);
    $a = svc()->assign($event, $role, $member);

    expect(fn () => svc()->respond($a, $member, DutyStatus::Assigned))
        ->toThrow(RosterRuleViolation::class, 'accept or decline');
});

test('decline then accept is refused and status stays declined', function () {
    [$event, $role] = rosterEvent();
    $member = rosterMember($event);
    $a = svc()->assign($event, $role, $member);
    svc()->respond($a, $member, DutyStatus::Declined);

    expect(fn () => svc()->respond($a, $member, DutyStatus::Accepted))
        ->toThrow(RosterRuleViolation::class, 'already responded');
    expect($a->fresh()->status)->toBe(DutyStatus::Declined);
});
