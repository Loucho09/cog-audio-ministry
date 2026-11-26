<?php

use App\Enums\DutyStatus;
use App\Models\Branch;
use App\Models\DutyAssignment;
use App\Models\Event;
use App\Models\User;

function memberDuty(User $user, array $event = [], array $attrs = []): DutyAssignment
{
    $event = Event::factory()->create(['branch_id' => $user->branch_id] + $event);

    return DutyAssignment::factory()->create(['event_id' => $event->id, 'user_id' => $user->id] + $attrs);
}

it('lists own upcoming duties and not others or past ones', function () {
    $branch = Branch::factory()->create();
    $me = User::factory()->member($branch)->create();
    $other = User::factory()->member($branch)->create();

    memberDuty($me, ['name' => 'Sunday Service Mine']);
    memberDuty($other, ['name' => 'Someone Elses Event']);
    memberDuty($me, ['name' => 'Long Gone Event', 'starts_at' => now()->subDays(3), 'ends_at' => now()->subDays(3)->addHours(2)]);

    $this->actingAs($me)->get('/dashboard')
        ->assertOk()
        ->assertSee('Sunday Service Mine')
        ->assertSee('Accept Sunday Service Mine duty', false)
        ->assertDontSee('Someone Elses Event')
        ->assertDontSee('Long Gone Event');
});

it('renders a working decline form with a csrf token and confirm text', function () {
    $me = User::factory()->member(Branch::factory()->create())->create();
    $duty = memberDuty($me, ['name' => 'Decline Me Event']);

    $html = $this->actingAs($me)->get('/dashboard')->assertOk()->getContent();

    expect($html)->not->toContain('@csrf')
        ->and($html)->not->toContain('@js(');

    preg_match('#<form[^>]*action="'.preg_quote(route('duties.decline', $duty), '#').'"[^>]*>(.*?)</form>#s', $html, $m);
    expect($m)->not->toBeEmpty()
        ->and($m[0])->toContain('name="_token"')
        ->and($m[0])->toContain('type="submit"')
        ->and($m[0])->toContain('Decline this duty?');
});

it('renders secondary buttons as submit when asked and as button by default', function () {
    $submit = (string) $this->blade('<x-secondary-button type="submit">Go</x-secondary-button>');
    $plain = (string) $this->blade('<x-secondary-button>Go</x-secondary-button>');

    expect($submit)->toContain('type="submit"')->not->toContain('type="button"')
        ->and($plain)->toContain('type="button"');
});

it('shows an empty state and hides buttons for non-assigned duties', function () {
    $me = User::factory()->member(Branch::factory()->create())->create();
    $this->actingAs($me)->get('/dashboard')->assertOk()
        ->assertSee('No duties yet. Your branch admin will assign you.');

    memberDuty($me, ['name' => 'Done Event'], ['status' => DutyStatus::Declined]);
    $this->get('/dashboard')->assertOk()
        ->assertSee('Done Event')
        ->assertSee('Declined')
        ->assertDontSee('Accept Done Event duty', false);
});

it('lets a member accept their duty', function () {
    $me = User::factory()->member(Branch::factory()->create())->create();
    $a = memberDuty($me);

    $this->actingAs($me)->post(route('duties.accept', $a))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'Thanks! You accepted this duty.');

    expect($a->fresh()->status)->toBe(DutyStatus::Accepted);
});

it('lets a member decline their duty', function () {
    $me = User::factory()->member(Branch::factory()->create())->create();
    $a = memberDuty($me);

    $this->actingAs($me)->post(route('duties.decline', $a))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'You declined this duty.');

    expect($a->fresh()->status)->toBe(DutyStatus::Declined);
});

it('forbids answering someone else\'s duty', function () {
    $branch = Branch::factory()->create();
    $me = User::factory()->member($branch)->create();
    $a = memberDuty(User::factory()->member($branch)->create());

    $this->actingAs($me)->post(route('duties.accept', $a))->assertForbidden();

    expect($a->fresh()->status)->toBe(DutyStatus::Assigned);
});

it('does not change status on a second answer', function () {
    $me = User::factory()->member(Branch::factory()->create())->create();
    $a = memberDuty($me);

    $this->actingAs($me)->post(route('duties.decline', $a));
    $this->post(route('duties.accept', $a))
        ->assertStatus(302)
        ->assertSessionHas('error');

    expect($a->fresh()->status)->toBe(DutyStatus::Declined);
});

it('requires login to answer', function () {
    $a = memberDuty(User::factory()->member(Branch::factory()->create())->create());
    $this->post(route('duties.accept', $a))->assertRedirect(route('login'));
});

it('asks before declining and hides the buttons once the event has started', function () {
    $me = User::factory()->member(Branch::factory()->create())->create();
    memberDuty($me, ['name' => 'Fresh Event']);
    memberDuty($me, [
        'name' => 'Running Event',
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ]);

    $this->actingAs($me)->get('/dashboard')->assertOk()
        ->assertSee('onsubmit="return confirm(', false)
        ->assertSee('Decline this duty?', false)
        ->assertSee('Accept Fresh Event duty', false)
        ->assertSee('Running Event')
        ->assertDontSee('Accept Running Event duty', false)
        ->assertDontSee('Decline Running Event duty', false);
});
