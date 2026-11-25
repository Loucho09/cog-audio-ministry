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

it('lands a flagged user on the dashboard after login, with the warning banner', function () {
    $user = flaggedUser(['email' => 'flagged@example.test']);

    $this->post('/login', ['email' => 'flagged@example.test', 'password' => 'temp-Pass-123'])
        ->assertRedirect(route('dashboard'));

    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('You\'re using a temporary password', false)
        ->assertSee(route('password.change'), false);
});

it('lets a flagged user use the profile and shows the banner there', function () {
    $this->actingAs(flaggedUser())->get('/profile')
        ->assertOk()
        ->assertSee('temporary password');
});

it('shows no banner to an unflagged user', function () {
    $this->actingAs(User::factory()->member(Branch::factory()->create())->create())
        ->get('/dashboard')->assertOk()->assertDontSee('temporary password');
});

it('redirects a flagged branch-admin away from the admin panel', function () {
    $user = User::factory()->branchAdmin(Branch::factory()->create())->create(['must_change_password' => true]);

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

    $this->get('/dashboard')->assertOk()->assertDontSee('temporary password');
});

it('lets an admin into the panel once the password is changed', function () {
    $user = User::factory()->superAdmin()->create([
        'password' => Hash::make('temp-Pass-123'),
        'must_change_password' => true,
    ]);

    $this->actingAs($user)->get('/admin')->assertRedirect(route('password.change'));

    $this->post(route('password.change.store'), [
        'current_password' => 'temp-Pass-123',
        'password' => 'Brand-new-Pass-9',
        'password_confirmation' => 'Brand-new-Pass-9',
    ])->assertRedirect(route('dashboard'));

    $this->get('/admin')->assertOk();
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
    $head = User::factory()->branchAdmin(Branch::factory()->create())->create(['must_change_password' => true]);

    $this->actingAs($head)->post(route('filament.admin.auth.logout'))->assertRedirect();

    $this->assertGuest();
});

it('refuses the profile password update for a flagged user', function () {
    $user = flaggedUser();

    $this->actingAs($user)->put(route('password.update'), [
        'current_password' => 'temp-Pass-123',
        'password' => 'Brand-new-Pass-9',
        'password_confirmation' => 'Brand-new-Pass-9',
    ])->assertRedirect(route('password.change'));

    $user->refresh();
    expect($user->must_change_password)->toBeTrue()
        ->and(Hash::check('temp-Pass-123', $user->password))->toBeTrue();
});

it('still lets an unflagged user update the password from the profile', function () {
    $user = User::factory()->member(Branch::factory()->create())->create(['password' => Hash::make('Old-Pass-12345')]);

    $this->actingAs($user)->from('/profile')->put(route('password.update'), [
        'current_password' => 'Old-Pass-12345',
        'password' => 'Brand-new-Pass-9',
        'password_confirmation' => 'Brand-new-Pass-9',
    ])->assertSessionHasNoErrors()->assertRedirect('/profile');

    expect(Hash::check('Brand-new-Pass-9', $user->refresh()->password))->toBeTrue();
});

it('answers 403 to Livewire and JSON requests from a flagged user', function () {
    $this->actingAs(flaggedUser());

    $this->withHeader('X-Livewire', 'true')->post(Livewire::getUpdateUri(), [])->assertForbidden();
    $this->getJson('/dashboard')->assertForbidden();
    $this->getJson('/admin')->assertForbidden();
});

it('blocks any livewire path for a flagged user without special headers', function () {
    $this->actingAs(flaggedUser())->post(route('livewire.upload-file'))->assertForbidden();
});

it('refuses profile update for a flagged user', function () {
    $user = flaggedUser(['email' => 'keep@example.test']);

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Changed', 'email' => 'new@example.test',
    ])->assertRedirect(route('password.change'));

    expect($user->refresh()->email)->toBe('keep@example.test');
});

it('refuses profile deletion for a flagged user', function () {
    $user = flaggedUser();

    $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'temp-Pass-123'])
        ->assertRedirect(route('password.change'));

    $this->assertModelExists($user);
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
