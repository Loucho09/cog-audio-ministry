<?php

use App\Models\User;
use App\Notifications\QueuedResetPassword;
use App\Notifications\WelcomeSetPassword;
use App\Support\InviteToken;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, QueuedResetPassword::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, QueuedResetPassword::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, QueuedResetPassword::class, function ($notification) use ($user) {
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
    Notification::assertSentTo($user, QueuedResetPassword::class);
    Notification::assertCount(1);
});

test('a successful reset clears the must-change-password flag', function () {
    Notification::fake();
    $user = User::factory()->create();
    $user->forceFill(['must_change_password' => true])->save();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, QueuedResetPassword::class, function ($n) use ($user) {
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

test('the normal reset email and the welcome email are queued', function () {
    expect(new QueuedResetPassword('t'))->toBeInstanceOf(ShouldQueue::class)
        ->and(new WelcomeSetPassword('t'))->toBeInstanceOf(ShouldQueue::class);

    Queue::fake();
    $user = User::factory()->create();
    $this->post('/forgot-password', ['email' => $user->email]);

    Queue::assertPushed(SendQueuedNotifications::class);
});

test('a 2-day-old welcome token still works on the reset page', function () {
    $user = User::factory()->create();
    $token = Password::broker('invites')->createToken($user);
    DB::table('password_reset_tokens')->where('email', $user->email)->update(['created_at' => now()->subDays(2)]);

    $this->post('/reset-password', [
        'token' => InviteToken::wrap($token),
        'email' => $user->email,
        'password' => 'new-Password-123',
        'password_confirmation' => 'new-Password-123',
    ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

    expect(Hash::check('new-Password-123', $user->fresh()->password))->toBeTrue();
});

test('a 2-day-old normal reset token fails, even with a forged welcome marker', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);
    DB::table('password_reset_tokens')->where('email', $user->email)->update(['created_at' => now()->subDays(2)]);

    foreach ([$token, $token.'.'.str_repeat('a', 32)] as $submitted) {
        $this->post('/reset-password', [
            'token' => $submitted,
            'email' => $user->email,
            'password' => 'new-Password-123',
            'password_confirmation' => 'new-Password-123',
        ])->assertSessionHasErrors('email');
    }

    expect(Hash::check('new-Password-123', $user->fresh()->password))->toBeFalse();
});
