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

test('forgot password gives the same answer when throttled', function () {
    $user = User::factory()->create();

    $first = $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);
    $second = $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);

    expect($second->baseResponse->getSession()->get('status'))->toBe($first->baseResponse->getSession()->get('status'));
    $second->assertSessionHasNoErrors();
});

test('an old invite token stops working after a profile password change', function () {
    $user = User::factory()->create();
    $token = Password::broker('invites')->createToken($user);

    $this->actingAs($user)->put('/password', [
        'current_password' => 'password',
        'password' => 'brand-New-Pass-1',
        'password_confirmation' => 'brand-New-Pass-1',
    ])->assertSessionHasNoErrors();
    auth()->logout();

    $this->post('/reset-password', [
        'token' => InviteToken::wrap($token),
        'email' => $user->email,
        'password' => 'another-Pass-123',
        'password_confirmation' => 'another-Pass-123',
    ])->assertSessionHasErrors(['email' => 'This link has expired or was replaced by a newer one. Please request a new link, or ask your branch head.']);
});

test('an old invite token fails for an account re-created with the same email', function () {
    $user = User::factory()->create(['email' => 'again@example.com']);
    $token = Password::broker('invites')->createToken($user);
    $user->delete();
    User::factory()->create(['email' => 'again@example.com']);

    $this->post('/reset-password', [
        'token' => InviteToken::wrap($token),
        'email' => 'again@example.com',
        'password' => 'another-Pass-123',
        'password_confirmation' => 'another-Pass-123',
    ])->assertSessionHasErrors('email');
});

test('profile update and delete are throttled', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $i) {
        $this->actingAs($user)->patch('/profile', ['name' => 'N', 'email' => $user->email, 'current_password' => 'password']);
    }

    $this->actingAs($user)->patch('/profile', ['name' => 'N', 'email' => $user->email, 'current_password' => 'password'])->assertStatus(429);
    $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertStatus(429);
});

test('an invite link shows the set-password page with the first name', function () {
    $user = User::factory()->create(['name' => 'Maria Santos']);
    $token = InviteToken::wrap(Password::broker('invites')->createToken($user));

    $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
        ->assertOk()
        ->assertSee('Set your password')
        ->assertSee('Hi Maria, your application was approved.')
        ->assertSee('Save password and continue')
        ->assertDontSee('Reset password')
        ->assertDontSee('Choose a new password');
});

test('an invite link with another person\'s email does not reveal their name', function () {
    $holder = User::factory()->create(['name' => 'Maria Santos']);
    $victim = User::factory()->create(['name' => 'Pedro Cruz']);
    $token = InviteToken::wrap(Password::broker('invites')->createToken($holder));

    $this->get(route('password.reset', ['token' => $token, 'email' => $victim->email]))
        ->assertOk()
        ->assertSee('Set your password')
        ->assertDontSee('Pedro')
        ->assertDontSee('Maria');
});

test('a normal reset link shows the choose-new-password page and no name', function () {
    $user = User::factory()->create(['name' => 'Maria Santos']);
    $token = Password::broker()->createToken($user);

    $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
        ->assertOk()
        ->assertSee('Choose a new password')
        ->assertSee('Enter a new password for your account.')
        ->assertSee('Save new password')
        ->assertDontSee('Maria')
        ->assertDontSee('Set your password');
});

test('an expired invite token shows the friendly expired message', function () {
    $user = User::factory()->create();
    $token = Password::broker('invites')->createToken($user);
    DB::table('password_reset_tokens')->where('email', $user->email)->update(['created_at' => now()->subDays(4)]);

    $this->from(route('password.reset', ['token' => InviteToken::wrap($token), 'email' => $user->email]))
        ->post('/reset-password', [
            'token' => InviteToken::wrap($token),
            'email' => $user->email,
            'password' => 'new-Password-123',
            'password_confirmation' => 'new-Password-123',
        ])->assertSessionHasErrors('email');

    $this->get(route('password.reset', ['token' => InviteToken::wrap($token), 'email' => $user->email]))
        ->assertOk();

    $this->followingRedirects()
        ->from(route('password.reset', ['token' => InviteToken::wrap($token), 'email' => $user->email]))
        ->post('/reset-password', [
            'token' => InviteToken::wrap($token),
            'email' => $user->email,
            'password' => 'new-Password-123',
            'password_confirmation' => 'new-Password-123',
        ])
        ->assertSee('This link has expired or was already used. Ask your branch head or the super-admin to send a new one, or use Forgot password.');
});

test('a successful invite reset redirects to login with the success status', function () {
    $user = User::factory()->create();
    $token = Password::broker('invites')->createToken($user);

    $this->post('/reset-password', [
        'token' => InviteToken::wrap($token),
        'email' => $user->email,
        'password' => 'new-Password-123',
        'password_confirmation' => 'new-Password-123',
    ])->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Your password is set. Log in with your email and new password.');
});

test('the email field on the reset page is read-only', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
        ->assertOk()
        ->assertSeeHtml('name="email"')
        ->assertSeeHtml('readonly');
});
