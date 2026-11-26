<?php

use App\Models\Branch;
use App\Models\DutyAssignment;
use App\Models\Event;
use App\Models\JoinApplication;
use App\Models\User;

test('guests are redirected to login', function () {
    $this->get('/dashboard')->assertRedirect(route('login'));
});

test('a branch admin gets the admin dashboard and not the member sections', function () {
    $branch = Branch::factory()->create();

    $this->actingAs(User::factory()->branchAdmin($branch)->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertViewIs('dashboard-admin')
        ->assertSee('Needs attention')
        ->assertSee('Quick actions')
        ->assertDontSee('My upcoming duties')
        ->assertDontSee('Open swaps in your branch')
        ->assertDontSee('My availability');
});

test('a member still gets the member dashboard', function () {
    $this->actingAs(User::factory()->member(Branch::factory()->create())->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertViewIs('dashboard')
        ->assertSee('My upcoming duties')
        ->assertDontSee('Needs attention');
});

test('a branch admin never sees another branch names or counts', function () {
    $a = Branch::factory()->create(['name' => 'Alpha Branch']);
    $b = Branch::factory()->create(['name' => 'Bravo Branch']);
    JoinApplication::factory()->pending()->count(7)->create(['branch_id' => $b->id]);

    $other = Event::factory()->create(['branch_id' => $b->id, 'name' => 'Bravo Secret Event', 'starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHours(2)]);
    DutyAssignment::factory()->create(['event_id' => $other->id, 'user_id' => User::factory()->member($b)->create(['name' => 'Bravo Person'])->id]);

    $this->actingAs(User::factory()->branchAdmin($a)->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Bravo')
        ->assertViewHas('groups', fn ($groups) => $groups->count() === 1 && $groups[0]['attention']['applications'] === 0);
});

test('the super admin sees every branch with its own numbers', function () {
    $a = Branch::factory()->create(['name' => 'Alpha Branch']);
    $b = Branch::factory()->create(['name' => 'Bravo Branch']);
    JoinApplication::factory()->pending()->count(3)->create(['branch_id' => $b->id]);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertViewIs('dashboard-admin')
        ->assertSeeInOrder(['Alpha Branch', 'Bravo Branch'])
        ->assertViewHas('groups', fn ($groups) => $groups->count() === 2 && $groups[1]['attention']['applications'] === 3);
});

test('empty states and links render for a quiet branch', function () {
    $branch = Branch::factory()->create();

    $this->actingAs(User::factory()->branchAdmin($branch)->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('All caught up')
        ->assertSee('Everyone has replied')
        ->assertSee('No one has marked a day off yet.')
        ->assertSee('/admin/events/create', false)
        ->assertSee('/admin/join-applications', false)
        ->assertSee('/admin/duty-swaps', false);
});

test('open slots show the gap and link to the event page', function () {
    $branch = Branch::factory()->create();
    [$event, $role] = rosterEvent(quantity: 2, like: Event::factory()->create(['branch_id' => $branch->id]));
    $event->update(['name' => 'Sunday Gap Service', 'starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHours(2)]);

    $this->actingAs(User::factory()->branchAdmin($branch)->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Sunday Gap Service')
        ->assertSee("{$role->name} 0 of 2")
        ->assertSee('/admin/events/'.$event->id, false);
});
