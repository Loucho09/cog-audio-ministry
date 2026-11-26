<?php

use App\Models\Branch;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

function bellNote(User $user, array $data = [], bool $read = false): DatabaseNotification
{
    return $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\DutyAssigned',
        'data' => array_merge(['type' => 'duty_assigned', 'message' => 'You are on audio.', 'url' => route('dashboard')], $data),
        'read_at' => $read ? now() : null,
    ]);
}

function bellUser(): User
{
    return User::factory()->member(Branch::factory()->create())->create();
}

test('guests are redirected to login', function () {
    $this->get(route('notifications.index'))->assertRedirect(route('login'));
    $this->post(route('notifications.read', Str::uuid()))->assertRedirect(route('login'));
    $this->post(route('notifications.read-all'))->assertRedirect(route('login'));
});

test('bell shows the unread count and caps at 9+', function () {
    $user = bellUser();
    bellNote($user);
    bellNote($user);
    bellNote($user, read: true);

    $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Notifications, 2 unread');

    foreach (range(1, 9) as $i) {
        bellNote($user);
    }
    $this->actingAs($user)->get(route('dashboard'))->assertSee('Notifications, 11 unread')->assertSee('9+');
});

test('index lists only own notifications', function () {
    $user = bellUser();
    $other = bellUser();
    bellNote($user, ['message' => 'Mine here']);
    bellNote($other, ['message' => 'Theirs secret']);

    $this->actingAs($user)->get(route('notifications.index'))
        ->assertOk()->assertSee('Mine here')->assertDontSee('Theirs secret');
});

test('read marks only own notification and redirects to payload url', function () {
    $user = bellUser();
    $note = bellNote($user, ['url' => '/duties/swap-here']);

    $this->actingAs($user)->post(route('notifications.read', $note->id))->assertRedirect('/duties/swap-here');
    expect($note->fresh()->read_at)->not->toBeNull();
});

test('read cannot touch another users notification', function () {
    $other = bellUser();
    $note = bellNote($other);

    $this->actingAs(bellUser())->post(route('notifications.read', $note->id))->assertNotFound();
    expect($note->fresh()->read_at)->toBeNull();
});

test('external or tricky urls redirect to dashboard', function (string $url) {
    $user = bellUser();
    $note = bellNote($user, ['url' => $url]);

    $this->actingAs($user)->post(route('notifications.read', $note->id))->assertRedirect(route('dashboard'));
})->with(['https://evil.test/x', '//evil.test', '/\\evil.test', 'javascript:alert(1)']);

test('same-app absolute urls are allowed', function () {
    $user = bellUser();
    $url = rtrim(config('app.url'), '/').'/dashboard';
    $note = bellNote($user, ['url' => $url]);

    $this->actingAs($user)->post(route('notifications.read', $note->id))->assertRedirect($url);
});

test('read-all marks only own notifications', function () {
    $user = bellUser();
    $other = bellUser();
    bellNote($user);
    bellNote($user);
    $theirs = bellNote($other);

    $this->actingAs($user)->from(route('notifications.index'))->post(route('notifications.read-all'))
        ->assertRedirect(route('notifications.index'));

    expect($user->unreadNotifications()->count())->toBe(0);
    expect($theirs->fresh()->read_at)->toBeNull();
});

test('message is escaped in bell and index', function () {
    $user = bellUser();
    bellNote($user, ['message' => '<script>alert(1)</script>']);

    $this->actingAs($user)->get(route('dashboard'))->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    $this->actingAs($user)->get(route('notifications.index'))->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
});
