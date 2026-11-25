<?php

use App\Models\Branch;
use App\Models\DutyRole;
use App\Models\Service;
use App\Models\ServiceDutySlot;
use App\Models\User;
use Illuminate\Database\QueryException;

test('duty role names are unique per branch', function () {
    $a = Branch::factory()->create();
    $b = Branch::factory()->create();

    DutyRole::factory()->create(['branch_id' => $a->id, 'name' => 'Sound']);
    DutyRole::factory()->create(['branch_id' => $b->id, 'name' => 'Sound']);

    expect(DutyRole::where('name', 'Sound')->count())->toBe(2);

    DutyRole::factory()->create(['branch_id' => $a->id, 'name' => 'Sound']);
})->throws(QueryException::class);

test('service has default duty slots', function () {
    $service = Service::factory()->create();
    $role = DutyRole::factory()->create(['branch_id' => $service->branch_id]);

    $service->dutySlots()->create(['duty_role_id' => $role->id, 'quantity' => 2]);

    $slot = $service->dutySlots()->first();
    expect($service->dutySlots)->toHaveCount(1)
        ->and($slot)->toBeInstanceOf(ServiceDutySlot::class)
        ->and($slot->quantity)->toBe(2)
        ->and($slot->dutyRole->is($role))->toBeTrue()
        ->and($slot->service->is($service))->toBeTrue();
});

test('visibleTo scopes duty roles to the user branch', function () {
    $a = Branch::factory()->create();
    $b = Branch::factory()->create();
    $mine = DutyRole::factory()->create(['branch_id' => $a->id]);
    DutyRole::factory()->create(['branch_id' => $b->id]);

    $head = User::factory()->branchHead($a)->create();
    $super = User::factory()->superAdmin()->create();

    expect(DutyRole::visibleTo($head)->pluck('id')->all())->toBe([$mine->id])
        ->and(DutyRole::visibleTo($super)->count())->toBe(2);
});
