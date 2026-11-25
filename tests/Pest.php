<?php

use App\Models\DutyRole;
use App\Models\Event;
use App\Models\User;
use App\Services\DutyRosterService;
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
