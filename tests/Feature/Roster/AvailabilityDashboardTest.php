<?php

use App\Enums\AvailabilityPart;
use App\Models\Branch;
use App\Models\MemberUnavailability;
use App\Models\User;

function dashMember(): User
{
    return User::factory()->member(Branch::factory()->create())->create();
}

it('lets a member mark a date and see it on the dashboard', function () {
    $me = dashMember();
    $date = now()->addDays(3)->toDateString();

    $this->actingAs($me)->post(route('availability.store'), ['date' => $date, 'part' => 'am', 'note' => 'Family trip'])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status');

    expect(MemberUnavailability::where('user_id', $me->id)->where('part', 'am')->count())->toBe(1);

    $this->get('/dashboard')->assertOk()
        ->assertSee('My availability')
        ->assertSee('Family trip')
        ->assertSee('3:00 PM');
});

it('shows an empty state', function () {
    $this->actingAs(dashMember())->get('/dashboard')->assertOk()
        ->assertSee('You have not marked any dates.');
});

it('validates date, part and note', function (Closure $payload, string $field) {
    $this->actingAs(dashMember())->post(route('availability.store'), $payload())
        ->assertSessionHasErrors($field);
    expect(MemberUnavailability::count())->toBe(0);
})->with([
    'past date' => [fn () => ['date' => now()->subDays(2)->toDateString(), 'part' => 'am'], 'date'],
    'bad part' => [fn () => ['date' => now()->addDay()->toDateString(), 'part' => 'night'], 'part'],
    'long note' => [fn () => ['date' => now()->addDay()->toDateString(), 'part' => 'am', 'note' => str_repeat('a', 101)], 'note'],
]);

it('flashes an error when the rule is broken', function () {
    [$event, $role] = rosterEvent('09:30', '11:00');
    $me = rosterMember($event);
    svc()->assign($event, $role, $me);

    $this->actingAs($me)->post(route('availability.store'), [
        'date' => $event->starts_at->toDateString(), 'part' => 'whole_day',
    ])->assertRedirect(route('dashboard'))
        ->assertSessionHas('availability_error');

    expect(MemberUnavailability::count())->toBe(0);
});

it('lets a member remove their own entry', function () {
    $me = dashMember();
    $entry = MemberUnavailability::create(['user_id' => $me->id, 'branch_id' => $me->branch_id, 'date' => now()->addDay()->toDateString(), 'part' => AvailabilityPart::Pm]);

    $this->actingAs($me)->delete(route('availability.destroy', $entry))->assertRedirect(route('dashboard'));
    expect(MemberUnavailability::count())->toBe(0);
});

it('forbids deleting another members entry', function () {
    $owner = dashMember();
    $entry = MemberUnavailability::create(['user_id' => $owner->id, 'branch_id' => $owner->branch_id, 'date' => now()->addDay()->toDateString(), 'part' => AvailabilityPart::Pm]);

    $this->actingAs(dashMember())->delete(route('availability.destroy', $entry))->assertForbidden();
    expect(MemberUnavailability::count())->toBe(1);
});

it('shows an error when deleting a past entry via the route', function () {
    $me = dashMember();
    $entry = MemberUnavailability::create(['user_id' => $me->id, 'branch_id' => $me->branch_id, 'date' => now()->subDay()->toDateString(), 'part' => AvailabilityPart::Pm]);

    $this->actingAs($me)->delete(route('availability.destroy', $entry))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('availability_error');
    expect(MemberUnavailability::count())->toBe(1);
});

it('validates the 6-month limit on the route', function () {
    $me = dashMember();
    $limit = now()->startOfDay()->addMonths(6);

    $this->actingAs($me)->post(route('availability.store'), ['date' => $limit->copy()->addDay()->toDateString(), 'part' => 'am'])
        ->assertSessionHasErrors('date');
    $this->post(route('availability.store'), ['date' => $limit->copy()->subDay()->toDateString(), 'part' => 'am'])
        ->assertSessionHasNoErrors();
    expect(MemberUnavailability::count())->toBe(1);
});

it('redirects guests to login', function () {
    $this->post(route('availability.store'), [])->assertRedirect('/login');
    $this->delete(route('availability.destroy', 1))->assertRedirect('/login');
});
