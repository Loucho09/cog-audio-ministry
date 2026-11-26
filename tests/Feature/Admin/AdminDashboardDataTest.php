<?php

use App\Actions\AdminDashboardData;
use App\Enums\AvailabilityPart;
use App\Enums\DutyStatus;
use App\Models\Branch;
use App\Models\DutyAssignment;
use App\Models\JoinApplication;
use App\Models\MemberUnavailability;
use App\Models\User;

function adminData(User $admin)
{
    return app(AdminDashboardData::class)->for($admin);
}

test('a branch admin only gets their own branch group', function () {
    $a = Branch::factory()->create();
    $b = Branch::factory()->create();
    JoinApplication::factory()->pending()->count(2)->create(['branch_id' => $a->id]);
    JoinApplication::factory()->pending()->count(5)->create(['branch_id' => $b->id]);

    $groups = adminData(User::factory()->branchAdmin($a)->create());

    expect($groups)->toHaveCount(1)
        ->and($groups[0]['branch']->id)->toBe($a->id)
        ->and($groups[0]['attention']['applications'])->toBe(2);
});

test('super admin gets every branch', function () {
    Branch::factory()->count(3)->create();

    expect(adminData(User::factory()->superAdmin()->create()))->toHaveCount(3);
});

test('needs attention counts applications, claimed swaps and declined upcoming duties', function () {
    [$swap, $duty, $claimer] = claimedSwap();
    $branch = $duty->event->branch;
    $duty->event->dutySlots()->update(['quantity' => 2]);
    JoinApplication::factory()->pending()->create(['branch_id' => $branch->id]);
    JoinApplication::factory()->approved()->create(['branch_id' => $branch->id]);
    DutyAssignment::factory()->create([
        'event_id' => $duty->event_id, 'duty_role_id' => $duty->duty_role_id,
        'user_id' => User::factory()->member($branch)->create()->id, 'status' => DutyStatus::Declined,
    ]);

    $attention = adminData(User::factory()->branchAdmin($branch)->create())[0]['attention'];

    expect($attention)->toBe(['applications' => 1, 'swaps' => 1, 'declined' => 1]);
});

test('unfilled slots list the gap per role with an event link', function () {
    [$event, $role] = rosterEvent(quantity: 2);
    $event->update(['starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(3)->addHours(2)]);
    DutyAssignment::factory()->create(['event_id' => $event->id, 'duty_role_id' => $role->id, 'user_id' => rosterMember($event)->id]);

    $unfilled = adminData(User::factory()->branchAdmin($event->branch)->create())[0]['unfilled'];

    expect($unfilled)->toHaveCount(1)
        ->and($unfilled[0]['gaps'])->toBe(["{$role->name} 1 of 2"])
        ->and($unfilled[0]['url'])->toContain('/admin');
});

test('full events and events beyond 14 days are not unfilled', function () {
    [$full, $role] = rosterEvent();
    $full->update(['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHours(2)]);
    DutyAssignment::factory()->create(['event_id' => $full->id, 'duty_role_id' => $role->id, 'user_id' => rosterMember($full)->id]);
    [$far] = rosterEvent(like: $full);
    $far->update(['starts_at' => now()->addDays(20), 'ends_at' => now()->addDays(20)->addHours(2)]);

    expect(adminData(User::factory()->branchAdmin($full->branch)->create())[0]['unfilled'])->toBeEmpty();
});

test('waiting for reply lists assigned duties nearest first and caps at 10', function () {
    [$event, $role] = rosterEvent(quantity: 12);
    $branch = $event->branch;
    foreach (range(1, 12) as $i) {
        DutyAssignment::factory()->create(['event_id' => $event->id, 'duty_role_id' => $role->id, 'user_id' => User::factory()->member($branch)->create()->id]);
    }
    [$sooner, $r2] = rosterEvent(like: $event);
    $sooner->update(['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHours(2)]);
    $first = DutyAssignment::factory()->create(['event_id' => $sooner->id, 'duty_role_id' => $r2->id, 'user_id' => User::factory()->member($branch)->create()->id]);
    DutyAssignment::factory()->create(['event_id' => $sooner->id, 'duty_role_id' => $r2->id, 'user_id' => User::factory()->member($branch)->create()->id, 'status' => DutyStatus::Accepted]);

    $waiting = adminData(User::factory()->branchAdmin($branch)->create())[0]['waiting'];

    expect($waiting)->toHaveCount(10)->and($waiting[0]->id)->toBe($first->id);
});

test('unavailable lists the next 14 days only', function () {
    $branch = Branch::factory()->create();
    $m = User::factory()->member($branch)->create();
    MemberUnavailability::create(['user_id' => $m->id, 'branch_id' => $branch->id, 'date' => now()->addDays(3)->toDateString(), 'part' => AvailabilityPart::Am, 'note' => 'Exam']);
    MemberUnavailability::create(['user_id' => $m->id, 'branch_id' => $branch->id, 'date' => now()->addDays(30)->toDateString(), 'part' => AvailabilityPart::Pm, 'note' => 'Far away']);

    $rows = adminData(User::factory()->branchAdmin($branch)->create())[0]['unavailable'];

    expect($rows)->toHaveCount(1)->and($rows[0]->note)->toBe('Exam');
});

test('snapshot counts members and recent answers, excluding admins', function () {
    [$event, $role] = rosterEvent(quantity: 3);
    $branch = $event->branch;
    User::factory()->branchAdmin($branch)->create();
    foreach ([DutyStatus::Accepted, DutyStatus::Accepted, DutyStatus::Declined] as $status) {
        DutyAssignment::factory()->create([
            'event_id' => $event->id, 'duty_role_id' => $role->id, 'user_id' => User::factory()->member($branch)->create()->id,
            'status' => $status, 'responded_at' => now()->subDays(2),
        ]);
    }
    DutyAssignment::factory()->create([
        'event_id' => $event->id, 'duty_role_id' => $role->id, 'user_id' => User::factory()->member($branch)->create()->id,
        'status' => DutyStatus::Accepted, 'responded_at' => now()->subDays(60),
    ]);

    $snap = adminData(User::factory()->branchAdmin($branch)->create())[0]['snapshot'];

    expect($snap['members'])->toBe(4)->and($snap['accepted'])->toBe(2)->and($snap['declined'])->toBe(1);
});

test('declined count drops once the declined seat is refilled', function () {
    [$event, $role] = rosterEvent();
    $branch = $event->branch;
    DutyAssignment::factory()->create(['event_id' => $event->id, 'duty_role_id' => $role->id, 'user_id' => User::factory()->member($branch)->create()->id, 'status' => DutyStatus::Declined]);
    $admin = User::factory()->branchAdmin($branch)->create();

    expect(adminData($admin)[0]['attention']['declined'])->toBe(1);

    DutyAssignment::factory()->create(['event_id' => $event->id, 'duty_role_id' => $role->id, 'user_id' => User::factory()->member($branch)->create()->id]);

    expect(adminData($admin)[0]['attention']['declined'])->toBe(0);
});

test('legacy admin-held duties are ignored in waiting and filled counts', function () {
    [$event, $role] = rosterEvent();
    $event->update(['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHours(2)]);
    $branch = $event->branch;
    $legacy = User::factory()->branchAdmin($branch)->create();
    DutyAssignment::factory()->create(['event_id' => $event->id, 'duty_role_id' => $role->id, 'user_id' => $legacy->id]);

    $group = adminData(User::factory()->branchAdmin($branch)->create())[0];

    expect($group['waiting'])->toBeEmpty()
        ->and($group['unfilled'][0]['gaps'])->toBe(["{$role->name} 0 of 1"]);
});

test('waiting for reply only covers the next 14 days', function () {
    [$event, $role] = rosterEvent();
    $event->update(['starts_at' => now()->addDays(20), 'ends_at' => now()->addDays(20)->addHours(2)]);
    DutyAssignment::factory()->create(['event_id' => $event->id, 'duty_role_id' => $role->id, 'user_id' => rosterMember($event)->id]);

    expect(adminData(User::factory()->branchAdmin($event->branch)->create())[0]['waiting'])->toBeEmpty();
});
