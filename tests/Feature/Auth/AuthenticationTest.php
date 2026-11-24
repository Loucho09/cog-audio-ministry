<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('two users sharing one IP do not lock each other out', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();

    foreach ([$a, $b] as $u) {
        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', ['email' => $u->email, 'password' => 'wrong-password']);
        }
    }

    $this->post('/login', ['email' => $a->email, 'password' => 'password']);
    $this->assertAuthenticatedAs($a);
    $this->post('/logout');

    $this->post('/login', ['email' => $b->email, 'password' => 'password']);
    $this->assertAuthenticatedAs($b);
});

test('the per-email login limit still triggers', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
    }

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertStatus(429);
    $this->assertGuest();
});
