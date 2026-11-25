<?php

use App\Enums\DutyStatus;
use App\Models\DutyAssignment;
use App\Models\DutyRole;
use App\Models\DutySwap;
use App\Models\Event;
use App\Models\User;
use App\Services\DutyRosterService;
use App\Services\DutySwapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/*
| Roster test helpers (shared by tests/Feature/Roster/*).
*/

function rosterEvent(string $start = '09:00', string $end = '11:00', int $quantity = 1, ?DutyRole $role = null, ?Event $like = null): array
{
    $day = Carbon::now()->addWeek()->startOfDay();
    $event = $like
        ? Event::factory()->create([
            'branch_id' => $like->branch_id,
            'starts_at' => $day->copy()->setTimeFromTimeString($start),
            'ends_at' => $day->copy()->setTimeFromTimeString($end),
        ])
        : Event::factory()->create([
            'starts_at' => $day->copy()->setTimeFromTimeString($start),
            'ends_at' => $day->copy()->setTimeFromTimeString($end),
        ]);
    $role ??= DutyRole::factory()->create(['branch_id' => $event->branch_id]);
    $event->dutySlots()->create(['duty_role_id' => $role->id, 'quantity' => $quantity]);

    return [$event, $role];
}

function rosterMember(Event $event): User
{
    return User::factory()->member($event->branch)->create();
}

function svc(): DutyRosterService
{
    return app(DutyRosterService::class);
}

function swapSvc(): DutySwapService
{
    return app(DutySwapService::class);
}

/** @return array{0: DutySwap, 1: DutyAssignment} */
function openSwap(string $start = '09:30', string $end = '11:00'): array
{
    [$event, $role] = rosterEvent($start, $end);
    $duty = DutyAssignment::factory()->create([
        'event_id' => $event->id,
        'duty_role_id' => $role->id,
        'user_id' => rosterMember($event)->id,
        'status' => DutyStatus::Accepted,
    ]);

    return [swapSvc()->offer($duty, $duty->user), $duty];
}

/** @return array{0: DutySwap, 1: DutyAssignment, 2: User} claimed swap, original duty, claimer */
function claimedSwap(string $start = '09:30', string $end = '11:00'): array
{
    [$swap, $duty] = openSwap($start, $end);
    $claimer = rosterMember($duty->event);
    swapSvc()->claim($swap, $claimer);

    return [$swap->fresh(), $duty, $claimer];
}

function branchAdminFor(DutyAssignment $duty): User
{
    return User::factory()->branchAdmin($duty->event->branch)->create();
}

function activeCount(DutyAssignment $duty): int
{
    return DutyAssignment::query()->active()
        ->where('event_id', $duty->event_id)
        ->where('duty_role_id', $duty->duty_role_id)
        ->count();
}

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
