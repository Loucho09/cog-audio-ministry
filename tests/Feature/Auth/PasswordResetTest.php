<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-Password-123',
            'password_confirmation' => 'new-Password-123',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });
});

test('forgot password answers the same for known and unknown emails', function () {
    Notification::fake();
    $user = User::factory()->create();

    $known = $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);
    $unknown = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'nobody@example.com']);

    $known->assertSessionHasNoErrors()->assertSessionHas('status');
    $unknown->assertSessionHasNoErrors()->assertSessionHas('status');
    expect($unknown->baseResponse->getSession()->get('status'))->toBe($known->baseResponse->getSession()->get('status'));
    Notification::assertSentTo($user, ResetPassword::class);
    Notification::assertCount(1);
});

test('a successful reset clears the must-change-password flag', function () {
    Notification::fake();
    $user = User::factory()->create();
    $user->forceFill(['must_change_password' => true])->save();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($n) use ($user) {
        $this->post('/reset-password', [
            'token' => $n->token,
            'email' => $user->email,
            'password' => 'new-Password-123',
            'password_confirmation' => 'new-Password-123',
        ])->assertSessionHasNoErrors();

        return true;
    });

    expect($user->fresh()->must_change_password)->toBeFalse();
});
