<?php

use App\Enums\DutyStatus;
use App\Models\Branch;
use App\Models\JoinApplication;
use App\Models\User;

it('shows Needs your reply only when a duty is assigned and not started', function () {
    $me = User::factory()->member(Branch::factory()->create())->create();

    $this->actingAs($me)->get('/dashboard')->assertOk()->assertDontSee('Needs your reply');

    memberDuty($me, ['name' => 'Accepted One'], ['status' => DutyStatus::Accepted]);
    $this->get('/dashboard')->assertOk()->assertDontSee('Needs your reply');

    memberDuty($me, ['name' => 'Waiting One']);
    $this->get('/dashboard')->assertOk()
        ->assertSeeInOrder(['Needs your reply', 'Waiting One', 'My upcoming duties', 'Accepted One']);
});

it('puts declined duties inside a details disclosure at the end', function () {
    $me = User::factory()->member(Branch::factory()->create())->create();
    memberDuty($me, ['name' => 'Turned Down Event'], ['status' => DutyStatus::Declined]);
    memberDuty($me, ['name' => 'Kept Event'], ['status' => DutyStatus::Accepted]);

    $html = $this->actingAs($me)->get('/dashboard')->assertOk()->getContent();

    expect($html)->toMatch('#<details[^>]*>\s*<summary[^>]*>\s*Declined \(1\)#')
        ->and(strpos($html, 'Kept Event'))->toBeLessThan(strpos($html, 'Turned Down Event'));
});

it('caps upcoming duties at five behind a show all disclosure', function () {
    $me = User::factory()->member(Branch::factory()->create())->create();
    foreach (range(1, 7) as $i) {
        memberDuty($me, ['name' => "Cap Event {$i}", 'starts_at' => now()->addDays($i), 'ends_at' => now()->addDays($i)->addHours(2)], ['status' => DutyStatus::Accepted]);
    }

    $this->actingAs($me)->get('/dashboard')->assertOk()->assertSee('Show all (7)');
});

it('no longer shows the announcements placeholder and has one h1', function () {
    $html = $this->actingAs(User::factory()->member(Branch::factory()->create())->create())
        ->get('/dashboard')->assertOk()
        ->assertDontSee('Announcements')
        ->assertDontSee('Coming soon')
        ->getContent();

    expect(substr_count($html, '<h1'))->toBe(1);
});

it('renders admin needs attention before quick actions as whole-card links', function () {
    $branch = Branch::factory()->create();
    JoinApplication::factory()->pending()->count(2)->create(['branch_id' => $branch->id]);

    $html = $this->actingAs(User::factory()->branchAdmin($branch)->create())
        ->get('/dashboard')->assertOk()->getContent();

    expect(strpos($html, 'Needs attention'))->toBeLessThan(strpos($html, 'Quick actions'))
        ->and(strpos($html, 'Open slots'))->toBeLessThan(strpos($html, 'Quick actions'))
        ->and(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toMatch('#<a href="[^"]*join-applications"[^>]*>\s*<span[^>]*>Join applications to review</span>#');
});
