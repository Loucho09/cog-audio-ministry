<?php

use App\Actions\NotifyStaffOfJoinApplication;
use App\Filament\Resources\JoinApplications\JoinApplicationResource;
use App\Models\Branch;
use App\Models\JoinApplication;
use App\Models\User;
use App\Notifications\NewJoinApplication;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Models\Role;

function staff(string $role, ?Branch $branch): User
{
    Role::findOrCreate($role, 'web');
    $user = User::factory()->create(['branch_id' => $branch?->id]);
    $user->assignRole($role);

    return $user;
}

function submission(Branch $branch, array $overrides = []): array
{
    return array_merge([
        'full_name' => 'Juan Dela Cruz',
        'age' => 22,
        'contact_number' => '09171234567',
        'email' => 'juan@example.com',
        'branch_id' => $branch->id,
        'interests' => ['FOH', 'Livestream'],
        'experience' => 'Some.',
        'availability' => 'Sundays.',
        'consent' => '1',
        'website' => '',
    ], $overrides);
}

beforeEach(function () {
    RateLimiter::clear(md5('join-form'.'join:127.0.0.1'));
    Notification::fake();
    $this->branch = Branch::factory()->create(['name' => 'Branch Alpha']);
    $this->other = Branch::factory()->create();
});

it('queues the notification to own-branch staff and super-admins only', function () {
    $head = staff('branch-head', $this->branch);
    $admin = staff('branch-admin', $this->branch);
    $super = staff('super-admin', null);
    $otherHead = staff('branch-head', $this->other);
    $otherAdmin = staff('branch-admin', $this->other);
    $member = staff('member', $this->branch);

    $this->post(route('join.store'), submission($this->branch))->assertRedirect(route('join.thanks'));

    Notification::assertSentTo([$head, $admin, $super], NewJoinApplication::class);
    Notification::assertNotSentTo([$otherHead, $otherAdmin, $member], NewJoinApplication::class);
    Notification::assertCount(3);
});

it('sends one notification to a user with several roles', function () {
    $both = staff('branch-head', $this->branch);
    Role::findOrCreate('branch-admin', 'web');
    $both->assignRole('branch-admin');
    $both->assignRole(Role::findOrCreate('super-admin', 'web'));

    $this->post(route('join.store'), submission($this->branch));

    Notification::assertSentToTimes($both, NewJoinApplication::class, 1);
});

it('does nothing quietly when there are no recipients, and the applicant is not emailed', function () {
    $this->post(route('join.store'), submission($this->branch))->assertRedirect(route('join.thanks'));

    Notification::assertNothingSent();
    expect(JoinApplication::count())->toBe(1);
});

it('does not notify the applicant', function () {
    staff('branch-head', $this->branch);
    $applicant = User::factory()->create(['email' => 'juan@example.com']);

    // Existing account blocks the submit; use another email to prove the applicant address is not a recipient.
    $this->post(route('join.store'), submission($this->branch, ['email' => 'new@example.com']));

    Notification::assertNotSentTo($applicant, NewJoinApplication::class);
    Notification::assertSentOnDemandTimes(NewJoinApplication::class, 0);
});

it('sends nothing for honeypot hits or invalid submissions', function () {
    staff('branch-head', $this->branch);

    $this->post(route('join.store'), submission($this->branch, ['website' => 'http://spam.test']))
        ->assertRedirect(route('join.thanks'));
    $this->post(route('join.store'), submission($this->branch, ['email' => 'nope']))
        ->assertSessionHasErrors('email');

    Notification::assertNothingSent();
    expect(JoinApplication::count())->toBe(0);
});

it('still redirects when queueing fails', function () {
    staff('branch-head', $this->branch);
    Exceptions::fake();
    Notification::shouldReceive('send')->andThrow(new RuntimeException('queue down'));

    $this->post(route('join.store'), submission($this->branch))->assertRedirect(route('join.thanks'));

    Exceptions::assertReported(RuntimeException::class);

    expect(JoinApplication::count())->toBe(1);
});

it('is a queued mail notification', function () {
    $app = JoinApplication::factory()->create(['branch_id' => $this->branch->id]);
    $notification = new NewJoinApplication($app);

    expect($notification)->toBeInstanceOf(ShouldQueue::class)
        ->and($notification->via(new User))->toBe(['mail']);
});

it('renders the mail with escaped applicant details', function () {
    $branch = Branch::factory()->create(['name' => 'Trece <b>Branch</b>']);
    $app = JoinApplication::factory()->create([
        'branch_id' => $branch->id,
        'full_name' => 'Maria <script>alert(1)</script>',
        'interests' => ['FOH', 'Monitors'],
    ]);

    $mail = (new NewJoinApplication($app))->toMail(new User);
    $html = (string) $mail->render();

    expect($mail->subject)->toContain('Trece')
        ->and($html)->toContain('Maria &lt;script&gt;')
        ->and($html)->not->toContain('<script>alert(1)')
        ->and($html)->toContain('Trece &lt;b&gt;Branch')
        ->and($html)->toContain('FOH, Monitors')
        ->and($html)->toContain(JoinApplicationResource::getUrl('view', ['record' => $app], panel: 'admin'));
});

it('exposes recipients through the action', function () {
    $head = staff('branch-head', $this->branch);
    staff('branch-head', $this->other);
    $app = JoinApplication::factory()->create(['branch_id' => $this->branch->id]);

    expect(app(NotifyStaffOfJoinApplication::class)->recipients($app)->pluck('id')->all())->toBe([$head->id]);
});

it('escapes markdown in names so they cannot form links or bold text', function () {
    $app = JoinApplication::factory()->create([
        'branch_id' => $this->branch->id,
        'full_name' => '[click](http://evil.test) **x**',
    ]);

    $html = (string) (new NewJoinApplication($app))->toMail(new User)->render();

    expect($html)->not->toContain('<a href="http://evil.test"')
        ->and($html)->not->toContain('<strong>')
        ->and($html)->toContain('click');
});

it('does not notify a branch-head without a branch', function () {
    $orphan = staff('branch-head', null);

    $this->post(route('join.store'), submission($this->branch));

    Notification::assertNotSentTo($orphan, NewJoinApplication::class);
    Notification::assertNothingSent();
});

it('queues a real job end to end and renders with the persisted model', function () {
    Notification::swap(new ChannelManager(app()));
    Queue::fake();
    $head = staff('branch-head', $this->branch);
    $app = JoinApplication::factory()->create(['branch_id' => $this->branch->id, 'full_name' => 'Ana Reyes']);

    app(NotifyStaffOfJoinApplication::class)->handle($app);

    Queue::assertPushed(SendQueuedNotifications::class);
    $html = (string) (new NewJoinApplication($app->fresh()))->toMail($head)->render();
    expect($html)->toContain('Ana Reyes')->toContain('Branch Alpha');
});
