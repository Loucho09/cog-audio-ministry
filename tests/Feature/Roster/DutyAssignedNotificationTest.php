<?php

use App\Models\Branch;
use App\Models\DutyAssignment;
use App\Models\User;
use App\Notifications\DutyAssigned;
use Illuminate\Support\Facades\Notification;

function memberFor($event, array $attrs = []): User
{
    return User::factory()->member($event->branch)->create($attrs);
}

test('assigning a member sends database and mail', function () {
    Notification::fake();
    [$event, $role] = rosterEvent();
    $member = memberFor($event);

    svc()->assign($event, $role, $member);

    Notification::assertSentTo($member, DutyAssigned::class, fn ($n, $channels) => $channels === ['database', 'mail']);
});

test('email off means database only', function () {
    Notification::fake();
    [$event, $role] = rosterEvent();
    $member = memberFor($event, ['email_notifications' => false]);

    svc()->assign($event, $role, $member);

    Notification::assertSentTo($member, DutyAssigned::class, fn ($n, $channels) => $channels === ['database']);
});

test('guest assignment sends nothing', function () {
    Notification::fake();
    [$event, $role] = rosterEvent();

    svc()->assignGuest($event, $role, 'Guest Person');

    Notification::assertNothingSent();
});

test('notify false and a failed assign send nothing', function () {
    Notification::fake();
    [$event, $role] = rosterEvent();
    $member = memberFor($event);

    svc()->assign($event, $role, $member, notify: false);
    expect(fn () => svc()->assign($event, $role, memberFor($event)))->toThrow(Exception::class);

    Notification::assertNothingSent();
});

test('approving a swap does not send duty assigned', function () {
    Notification::fake();
    [$swap, $duty] = openSwap();
    $claimer = User::factory()->member($duty->event->branch)->create();
    $admin = User::factory()->branchAdmin($duty->event->branch)->create();

    swapSvc()->claim($swap, $claimer);
    swapSvc()->approve($swap->fresh(), $admin);

    Notification::assertNotSentTo($claimer, DutyAssigned::class);
});

test('place uses address when filled, branch name otherwise', function () {
    [$event, $role] = rosterEvent();
    $member = memberFor($event);
    $assignment = DutyAssignment::factory()->create([
        'event_id' => $event->id, 'duty_role_id' => $role->id, 'user_id' => $member->id, 'branch_id' => $event->branch_id,
    ]);

    $event->branch->update(['address' => '12 Sample St']);
    expect((new DutyAssigned($assignment))->toArray($member)['place'])->toBe('12 Sample St');

    $event->branch->update(['address' => null]);
    $assignment = $assignment->fresh();
    expect((new DutyAssigned($assignment))->toArray($member)['place'])->toBe($event->branch->name);
});

test('satellite assignment names the event branch', function () {
    [$event, $role] = rosterEvent();
    $home = Branch::factory()->create();
    $member = User::factory()->member($home)->create();
    $assignment = DutyAssignment::factory()->create([
        'event_id' => $event->id, 'duty_role_id' => $role->id, 'user_id' => $member->id, 'branch_id' => $event->branch_id,
    ]);

    expect((new DutyAssigned($assignment))->toArray($member)['place'])->toContain($event->branch->name.' branch');
});

test('mail renders and escapes html in the event title', function () {
    [$event, $role] = rosterEvent();
    $event->update(['name' => '<script>alert(1)</script>']);
    $member = memberFor($event);
    $assignment = DutyAssignment::factory()->create([
        'event_id' => $event->id, 'duty_role_id' => $role->id, 'user_id' => $member->id, 'branch_id' => $event->branch_id,
    ]);

    $html = (string) (new DutyAssigned($assignment))->toMail($member)->render();

    expect($html)->not->toContain('<script>alert(1)</script>')
        ->and($html)->toContain('&lt;script&gt;')
        ->and($html)->toContain(route('dashboard'));
});

test('database payload has the bell fields', function () {
    [$event, $role] = rosterEvent();
    $member = memberFor($event);
    $assignment = DutyAssignment::factory()->create([
        'event_id' => $event->id, 'duty_role_id' => $role->id, 'user_id' => $member->id, 'branch_id' => $event->branch_id,
    ]);

    expect((new DutyAssigned($assignment))->toDatabase($member))
        ->toHaveKeys(['assignment_id', 'event_id', 'role', 'event_title', 'starts_at', 'place', 'message']);
});
