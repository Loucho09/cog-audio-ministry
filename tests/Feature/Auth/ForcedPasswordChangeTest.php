<?php

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

function flaggedUser(array $state = []): User
{
    return User::factory()->member(Branch::factory()->create())->create(array_merge([
        'password' => Hash::make('temp-Pass-123'),
        'must_change_password' => true,
    ], $state));
}

it('redirects a flagged user from dashboard, profile and admin', function (string $url) {
    $this->actingAs(flaggedUser());

    $this->get($url)->assertRedirect(route('password.change'));
})->with(['/dashboard', '/profile']);

it('redirects a flagged branch-head away from the admin panel', function () {
    $user = User::factory()->branchHead(Branch::factory()->create())->create(['must_change_password' => true]);

    $this->actingAs($user)->get('/admin')->assertRedirect(route('password.change'));
});

it('redirects a flagged super-admin away from the admin panel', function () {
    $user = User::factory()->superAdmin()->create(['must_change_password' => true]);

    $this->actingAs($user)->get('/admin')->assertRedirect(route('password.change'));
});

it('shows the change page to a flagged user', function () {
    $this->actingAs(flaggedUser())->get(route('password.change'))->assertOk();
});

it('lets a flagged user log out', function () {
    $this->actingAs(flaggedUser())->post(route('logout'))->assertRedirect('/');

    $this->assertGuest();
});

it('clears the flag and redirects to the dashboard after a valid change', function () {
    $user = flaggedUser();

    $this->actingAs($user)->post(route('password.change.store'), [
        'current_password' => 'temp-Pass-123',
        'password' => 'Brand-new-Pass-9',
        'password_confirmation' => 'Brand-new-Pass-9',
    ])->assertRedirect(route('dashboard'))->assertSessionHas('status');

    $user->refresh();
    expect($user->must_change_password)->toBeFalse()
        ->and(Hash::check('Brand-new-Pass-9', $user->password))->toBeTrue();

    $this->get('/dashboard')->assertOk();
});

it('rejects a new password equal to the current one', function () {
    $user = flaggedUser();

    $this->actingAs($user)->post(route('password.change.store'), [
        'current_password' => 'temp-Pass-123',
        'password' => 'temp-Pass-123',
        'password_confirmation' => 'temp-Pass-123',
    ])->assertSessionHasErrors('password');

    expect($user->refresh()->must_change_password)->toBeTrue();
});

it('rejects a wrong current password', function () {
    $user = flaggedUser();

    $this->actingAs($user)->post(route('password.change.store'), [
        'current_password' => 'wrong',
        'password' => 'Brand-new-Pass-9',
        'password_confirmation' => 'Brand-new-Pass-9',
    ])->assertSessionHasErrors('current_password');

    expect($user->refresh()->must_change_password)->toBeTrue();
});

it('does not redirect an unflagged user', function () {
    $user = User::factory()->member(Branch::factory()->create())->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();
    $this->actingAs($user)->get('/profile')->assertOk();
});

it('keeps guests away from the change page', function () {
    $this->get(route('password.change'))->assertRedirect(route('login'));
    $this->post(route('password.change.store'), [])->assertRedirect(route('login'));
});

it('does not allow mass assigning the flag', function () {
    $user = User::factory()->create();
    $user->fill(['must_change_password' => true])->save();

    expect($user->refresh()->must_change_password)->toBeFalse();
});

it('allows a flagged user to log out through the Filament route', function () {
    $head = User::factory()->branchHead(Branch::factory()->create())->create(['must_change_password' => true]);

    $this->actingAs($head)->post(route('filament.admin.auth.logout'))->assertRedirect();

    $this->assertGuest();
});

it('blocks a flagged user from verification, password update and profile update', function () {
    $user = flaggedUser(['email_verified_at' => null]);
    $this->actingAs($user);

    $this->post(route('verification.send'))->assertRedirect(route('password.change'));
    $this->put(route('password.update'), [
        'current_password' => 'temp-Pass-123',
        'password' => 'Brand-new-Pass-9',
        'password_confirmation' => 'Brand-new-Pass-9',
    ])->assertRedirect(route('password.change'));
    $this->patch(route('profile.update'), ['name' => 'X', 'email' => 'x@example.test'])->assertRedirect(route('password.change'));

    expect($user->refresh()->name)->not->toBe('X')
        ->and(Hash::check('temp-Pass-123', $user->password))->toBeTrue();
});

it('answers 403 to Livewire and JSON requests from a flagged user', function () {
    $this->actingAs(flaggedUser());

    $this->withHeader('X-Livewire', 'true')->post(Livewire::getUpdateUri(), [])->assertForbidden();
    $this->getJson('/dashboard')->assertForbidden();
});

it('cycles the remember token after a change', function () {
    $user = flaggedUser();
    $old = $user->remember_token;

    $this->actingAs($user)->post(route('password.change.store'), [
        'current_password' => 'temp-Pass-123',
        'password' => 'Brand-new-Pass-9',
        'password_confirmation' => 'Brand-new-Pass-9',
    ])->assertRedirect(route('dashboard'));

    expect($user->refresh()->remember_token)->not->toBe($old);
});

it('throttles the change endpoint after 5 attempts', function () {
    $user = flaggedUser();
    $this->actingAs($user);

    foreach (range(1, 5) as $i) {
        $this->post(route('password.change.store'), [
            'current_password' => 'wrong', 'password' => 'Brand-new-Pass-9', 'password_confirmation' => 'Brand-new-Pass-9',
        ])->assertSessionHasErrors('current_password');
    }

    $this->post(route('password.change.store'), [
        'current_password' => 'temp-Pass-123', 'password' => 'Brand-new-Pass-9', 'password_confirmation' => 'Brand-new-Pass-9',
    ])->assertStatus(429);

    expect($user->refresh()->must_change_password)->toBeTrue();
});

it('rejects weak new passwords', function () {
    $user = flaggedUser();

    $this->actingAs($user)->post(route('password.change.store'), [
        'current_password' => 'temp-Pass-123', 'password' => 'short1', 'password_confirmation' => 'short1',
    ])->assertSessionHasErrors('password');
});

it('sends an unflagged user from the change page to the profile', function () {
    $this->actingAs(User::factory()->member(Branch::factory()->create())->create())
        ->get(route('password.change'))->assertRedirect(route('profile.edit'));
});
