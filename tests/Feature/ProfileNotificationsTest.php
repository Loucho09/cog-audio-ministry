<?php

use App\Models\DutyAssignment;
use App\Models\User;
use App\Notifications\DutyAssigned;

function assignedChannels(User $user): array
{
    return (new DutyAssigned(new DutyAssignment))->via($user);
}

test('profile page shows the email notifications toggle', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/profile')
        ->assertOk()
        ->assertSee('Email me about my duties')
        ->assertSee('You will always see notifications in the app');
});

test('turning off leaves the database channel only and turning on restores mail', function () {
    $user = User::factory()->create(['email_notifications' => true]);

    $this->actingAs($user)
        ->patch(route('profile.notifications.update'))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', 'notifications-updated');

    $user->refresh();
    expect($user->email_notifications)->toBeFalse();
    expect(assignedChannels($user))->toBe(['database']);

    $this->actingAs($user)->patch(route('profile.notifications.update'), ['email_notifications' => '1']);

    $user->refresh();
    expect($user->email_notifications)->toBeTrue();
    expect(assignedChannels($user))->toBe(['database', 'mail']);
});

test('guest cannot update notification settings', function () {
    $this->patch(route('profile.notifications.update'), ['email_notifications' => '1'])
        ->assertRedirect(route('login'));
});

test('missing field means off', function () {
    $user = User::factory()->create(['email_notifications' => true]);

    $this->actingAs($user)->patch(route('profile.notifications.update'), [])->assertSessionHasNoErrors();

    expect($user->fresh()->email_notifications)->toBeFalse();
});

test('only the signed in user row changes', function () {
    $me = User::factory()->create(['email_notifications' => true]);
    $other = User::factory()->create(['email_notifications' => true]);

    $this->actingAs($me)->patch(route('profile.notifications.update'), ['user_id' => $other->id, 'id' => $other->id]);

    expect($me->fresh()->email_notifications)->toBeFalse();
    expect($other->fresh()->email_notifications)->toBeTrue();
});

test('main profile update cannot change email_notifications', function () {
    $user = User::factory()->create(['email_notifications' => true]);

    $this->actingAs($user)->patch('/profile', [
        'name' => 'New Name',
        'email' => $user->email,
        'email_notifications' => '0',
    ])->assertSessionHasNoErrors();

    expect($user->fresh()->name)->toBe('New Name');
    expect($user->fresh()->email_notifications)->toBeTrue();
});
