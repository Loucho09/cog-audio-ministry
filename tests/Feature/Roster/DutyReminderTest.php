<?php

use App\Enums\DutyStatus;
use App\Models\DutyAssignment;
use App\Models\Event;
use App\Models\User;
use App\Notifications\DutyReminder;
use App\Services\DutyRosterService;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Notifications\Dispatcher as NotificationDispatcher;
use Illuminate\Support\Facades\Notification;

function reminderDuty(int $hoursAhead, DutyStatus $status = DutyStatus::Accepted, array $userAttrs = [], bool $guest = false): DutyAssignment
{
    $start = now()->addHours($hoursAhead);
    $event = Event::factory()->create(['starts_at' => $start, 'ends_at' => $start->copy()->addHours(2)]);

    if ($guest) {
        return DutyAssignment::factory()->guest()->create(['event_id' => $event->id]);
    }

    $member = User::factory()->member($event->branch)->create($userAttrs);

    return DutyAssignment::factory()->create(['event_id' => $event->id, 'user_id' => $member->id, 'status' => $status]);
}

function runReminders(): void
{
    test()->artisan('duty:send-reminders')->assertSuccessful();
}

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-12-01 08:00:00'));
    Notification::fake();
});

afterEach(fn () => Carbon::setTestNow());

test('day before window sends the tomorrow reminder', function () {
    $a = reminderDuty(20);

    runReminders();

    Notification::assertSentTo($a->user, DutyReminder::class, fn ($n, $ch) => $n->kind === 'day_before' && $ch === ['database', 'mail']);
    expect($a->fresh()->reminded_day_at)->not->toBeNull()->and($a->fresh()->reminded_soon_at)->toBeNull();
});

test('soon window sends the soon reminder and marks both', function () {
    $a = reminderDuty(1);

    runReminders();

    Notification::assertSentTo($a->user, DutyReminder::class, fn ($n) => $n->kind === 'soon');
    Notification::assertCount(1);
    expect($a->fresh()->reminded_soon_at)->not->toBeNull()->and($a->fresh()->reminded_day_at)->not->toBeNull();
});

test('beyond the day window sends nothing', function () {
    reminderDuty(30);

    runReminders();

    Notification::assertNothingSent();
});

test('second run does not send again', function () {
    $a = reminderDuty(20);

    runReminders();
    runReminders();

    Notification::assertSentToTimes($a->user, DutyReminder::class, 1);
});

test('day reminder then soon reminder later', function () {
    $a = reminderDuty(20);
    runReminders();

    Carbon::setTestNow(now()->addHours(19));
    runReminders();

    Notification::assertSentToTimes($a->user, DutyReminder::class, 2);
});

test('only accepted duties are reminded', function () {
    reminderDuty(20, DutyStatus::Assigned);
    reminderDuty(20, DutyStatus::Declined);
    reminderDuty(1, DutyStatus::Swapped);

    runReminders();

    Notification::assertNothingSent();
});

test('guests are skipped', function () {
    reminderDuty(20, guest: true);

    runReminders();

    Notification::assertNothingSent();
});

test('past and started events are skipped', function () {
    reminderDuty(-1);
    reminderDuty(0);

    runReminders();

    Notification::assertNothingSent();
});

test('late accepted duty inside the soon window gets only the soon reminder', function () {
    $a = reminderDuty(1);

    runReminders();
    runReminders();

    Notification::assertSentToTimes($a->user, DutyReminder::class, 1);
});

test('email off means database only', function () {
    $a = reminderDuty(20, userAttrs: ['email_notifications' => false]);

    runReminders();

    Notification::assertSentTo($a->user, DutyReminder::class, fn ($n, $ch) => $ch === ['database']);
});

test('config offsets are respected', function () {
    config(['ministry.reminders' => ['day_before_hours' => 48, 'soon_hours' => 4]]);
    $day = reminderDuty(40);
    $soon = reminderDuty(3);
    $out = reminderDuty(60);

    runReminders();

    Notification::assertSentTo($day->user, DutyReminder::class, fn ($n) => $n->kind === 'day_before');
    Notification::assertSentTo($soon->user, DutyReminder::class, fn ($n) => $n->kind === 'soon');
    Notification::assertNotSentTo($out->user, DutyReminder::class);
});

test('payload carries type and message', function () {
    $a = reminderDuty(20);
    $payload = (new DutyReminder($a, 'day_before'))->toArray($a->user);
    $soon = (new DutyReminder($a, 'soon'))->toArray($a->user);

    expect($payload['type'])->toBe('duty_reminder_day')
        ->and($payload['message'])->toContain('tomorrow')
        ->and($soon['type'])->toBe('duty_reminder_soon')
        ->and($soon['message'])->toContain('in about 2 hours');
});

test('changing the event start resets reminders', function () {
    $a = reminderDuty(20);
    runReminders();
    expect($a->fresh()->reminded_day_at)->not->toBeNull();

    $event = $a->event;
    $a->forceFill(['reminded_soon_at' => now()])->save();
    app(DutyRosterService::class)->updateEvent($event, [
        'starts_at' => $event->starts_at->copy()->addDays(3),
        'ends_at' => $event->ends_at->copy()->addDays(3),
    ], [$a->duty_role_id => 1]);

    expect($a->fresh()->reminded_day_at)->toBeNull()->and($a->fresh()->reminded_soon_at)->toBeNull();
});

test('command is scheduled every five minutes', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($e) => str_contains($e->command, 'duty:send-reminders'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('*/5 * * * *')
        ->and($event->withoutOverlapping)->toBeTrue();
});

function failingDispatcher(): void
{
    $dispatcher = Mockery::mock(NotificationDispatcher::class);
    $dispatcher->shouldReceive('send')->andThrow(new RuntimeException('queue down'));
    app()->instance(NotificationDispatcher::class, $dispatcher);
}

test('claim update re-checks status so a decline after selection is not claimed or sent', function () {
    $a = reminderDuty(20);
    $a->update(['status' => DutyStatus::Declined]);

    runReminders();

    Notification::assertNothingSent();
    expect($a->fresh()->reminded_day_at)->toBeNull();
});

test('shouldSend is false when declined after claim', function () {
    $a = reminderDuty(20);
    $n = new DutyReminder($a, 'day_before');
    expect($n->shouldSend($a->user, 'mail'))->toBeTrue();

    $a->update(['status' => DutyStatus::Declined]);

    expect($n->shouldSend($a->user, 'mail'))->toBeFalse();
});

test('shouldSend is false when the event moved after claim', function () {
    $a = reminderDuty(20);
    $n = new DutyReminder($a, 'day_before');

    $a->event->update(['starts_at' => now()->addHours(22), 'ends_at' => now()->addHours(24)]);

    expect($n->shouldSend($a->user, 'database'))->toBeFalse();
});

test('shouldSend is false for another user or a started event', function () {
    $a = reminderDuty(20);
    $n = new DutyReminder($a, 'day_before');
    expect($n->shouldSend(User::factory()->create(), 'mail'))->toBeFalse();

    Carbon::setTestNow(now()->addHours(21));
    expect($n->shouldSend($a->user, 'mail'))->toBeFalse();
});

test('a failed notify releases the claim and the command fails', function () {
    $a = reminderDuty(20);
    failingDispatcher();

    $this->artisan('duty:send-reminders')->assertFailed();

    expect($a->fresh()->reminded_day_at)->toBeNull();
});

test('release restores a previous day reminder time for soon kind', function () {
    $a = reminderDuty(1);
    $prev = now()->subHours(20);
    DutyAssignment::query()->whereKey($a->id)->update(['reminded_day_at' => $prev]);
    failingDispatcher();

    $this->artisan('duty:send-reminders')->assertFailed();

    expect($a->fresh()->reminded_soon_at)->toBeNull()
        ->and($a->fresh()->reminded_day_at->equalTo($prev))->toBeTrue();
});

test('window boundaries', function () {
    $soonEdge = reminderDuty(2);
    $dayEdge = reminderDuty(24);
    $nowEdge = reminderDuty(0);

    runReminders();

    Notification::assertSentTo($soonEdge->user, DutyReminder::class, fn ($n) => $n->kind === 'soon');
    Notification::assertSentTo($dayEdge->user, DutyReminder::class, fn ($n) => $n->kind === 'day_before');
    Notification::assertNotSentTo($nowEdge->user, DutyReminder::class);
});

test('pre-claimed rows are skipped', function () {
    $a = reminderDuty(20);
    DutyAssignment::query()->whereKey($a->id)->update(['reminded_day_at' => now()]);

    runReminders();

    Notification::assertNothingSent();
});

test('event moved into the soon window after the day reminder still gets the soon reminder', function () {
    $a = reminderDuty(20);
    runReminders();

    $a->event->update(['starts_at' => now()->addHour(), 'ends_at' => now()->addHours(3)]);
    runReminders();

    Notification::assertSentToTimes($a->user, DutyReminder::class, 2);
    Notification::assertSentTo($a->user, DutyReminder::class, fn ($n) => $n->kind === 'soon');
});
