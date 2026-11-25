<?php

use App\Enums\DutyStatus;
use App\Models\DutyAssignment;
use App\Models\DutyRole;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('deleting a user with assignments is blocked', function () {
    $event = Event::factory()->create();
    $user = User::factory()->member($event->branch)->create();
    $role = DutyRole::factory()->create(['branch_id' => $event->branch_id]);
    DutyAssignment::factory()->create(['event_id' => $event->id, 'duty_role_id' => $role->id, 'user_id' => $user->id]);

    $user->delete();
})->throws(QueryException::class);

test('deleting an event deletes its slots and assignments', function () {
    $event = Event::factory()->create();
    $user = User::factory()->member($event->branch)->create();
    $role = DutyRole::factory()->create(['branch_id' => $event->branch_id]);
    $event->dutySlots()->create(['duty_role_id' => $role->id, 'quantity' => 1]);
    $assignment = DutyAssignment::factory()->create(['event_id' => $event->id, 'duty_role_id' => $role->id, 'user_id' => $user->id]);

    expect($assignment->branch_id)->toBe($event->branch_id);

    $event->delete();

    expect(DutyAssignment::count())->toBe(0)
        ->and(DB::table('event_duty_slots')->count())->toBe(0);
});

test('active scope excludes declined', function () {
    $event = Event::factory()->create();
    $role = DutyRole::factory()->create(['branch_id' => $event->branch_id]);
    foreach ([DutyStatus::Assigned, DutyStatus::Accepted, DutyStatus::Declined] as $status) {
        DutyAssignment::factory()->create([
            'event_id' => $event->id,
            'duty_role_id' => $role->id,
            'user_id' => User::factory()->member($event->branch),
            'status' => $status,
        ]);
    }

    expect(DutyAssignment::active()->count())->toBe(2)
        ->and(Event::upcoming()->count())->toBe(1)
        ->and($event->hasStarted())->toBeFalse();
});
