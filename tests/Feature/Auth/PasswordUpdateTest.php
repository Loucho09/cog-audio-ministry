<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertTrue(Hash::check('new-password-123', $user->refresh()->password));
});

test('updating password rotates remember token and keeps this session signed in', function () {
    $user = User::factory()->create(['remember_token' => 'old-token']);

    $this->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->remember_token)->not->toBe('old-token');
    $this->assertAuthenticatedAs($user);
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

    $response
        ->assertSessionHasErrorsIn('updatePassword', 'current_password')
        ->assertRedirect('/profile');
});
