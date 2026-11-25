<?php

use App\Models\Branch;
use App\Models\DutyAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'current_password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});

test('changing the email requires the current password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->from('/profile')
        ->patch('/profile', ['name' => 'X', 'email' => 'new@example.com'])
        ->assertSessionHasErrors('current_password');

    $this->actingAs($user)->from('/profile')
        ->patch('/profile', ['name' => 'X', 'email' => 'new@example.com', 'current_password' => 'wrong'])
        ->assertSessionHasErrors('current_password');

    expect($user->fresh()->email)->not->toBe('new@example.com');
});

test('a name-only change needs no password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/profile', ['name' => 'Only Name', 'email' => $user->email])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->name)->toBe('Only Name');
});

test('a member can delete their account and their role rows go too', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->member($branch)->create();
    $id = $user->id;

    $this->actingAs($user)
        ->delete('/profile', ['password' => 'password'])
        ->assertRedirect('/');

    expect(User::find($id))->toBeNull()
        ->and(DB::table('model_has_roles')->where('model_id', $id)->count())->toBe(0);
});

test('admins cannot delete their own account and do not see the section', function (string $role) {
    $branch = Branch::factory()->create();
    $user = match ($role) {
        'super-admin' => User::factory()->superAdmin()->create(),
        'branch-head' => User::factory()->branchHead($branch)->create(),
        'branch-admin' => User::factory()->branchAdmin($branch)->create(),
    };

    $this->actingAs($user)
        ->delete('/profile', ['password' => 'password'])
        ->assertForbidden();

    expect($user->fresh())->not->toBeNull();

    $this->actingAs($user)->get('/profile')->assertOk()->assertDontSee('confirm-user-deletion');
})->with(['super-admin', 'branch-head', 'branch-admin']);

test('a member with duty history gets a friendly refusal instead of an error', function () {
    $user = User::factory()->create();
    DutyAssignment::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->from('/profile')
        ->delete('/profile', ['password' => 'password'])
        ->assertRedirect('/profile')
        ->assertSessionHasErrorsIn('userDeletion', ['password']);

    $this->assertAuthenticatedAs($user);
    expect($user->fresh())->not->toBeNull();
});
