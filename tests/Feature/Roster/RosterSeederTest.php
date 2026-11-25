<?php

use App\Enums\DutyStatus;
use App\Models\Branch;
use App\Models\DutyAssignment;
use App\Models\Event;
use App\Models\EventDutySlot;
use App\Models\Service;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DutyRosterSeeder;

it('seeds a demo roster with every status and no overfull slot', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Event::count())->toBeGreaterThan(0);

    foreach ([DutyStatus::Assigned, DutyStatus::Accepted, DutyStatus::Declined] as $status) {
        expect(DutyAssignment::where('status', $status)->count())->toBeGreaterThan(0);
    }

    $totalSlots = 0;
    foreach (EventDutySlot::all() as $slot) {
        $filled = DutyAssignment::active()
            ->where('event_id', $slot->event_id)
            ->where('duty_role_id', $slot->duty_role_id)
            ->count();
        expect($filled)->toBeLessThanOrEqual($slot->quantity);
        $totalSlots += $slot->quantity;
    }

    expect(DutyAssignment::active()->count())->toBeLessThan($totalSlots);
    expect(DutyAssignment::whereNull('branch_id')->count())->toBe(0);
});

it('can be seeded twice without error', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Event::count())->toBeGreaterThan(0);
});

it('skips the demo roster outside local and testing', function () {
    Service::factory()->create(['branch_id' => Branch::factory()->create()->id]);
    $this->app['env'] = 'production';

    app()->call([new DutyRosterSeeder, 'run']);

    expect(Event::count())->toBe(0);
});
