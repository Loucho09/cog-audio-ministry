<?php

use App\Actions\ApproveJoinApplication;
use App\Actions\RejectJoinApplication;
use App\Exceptions\JoinApplicationReviewException;
use App\Filament\Resources\JoinApplications\Pages\ListJoinApplications;
use App\Filament\Resources\JoinApplications\Pages\ViewJoinApplication;
use App\Models\Branch;
use App\Models\JoinApplication;
use App\Models\User;
use App\Notifications\WelcomeSetPassword;
use App\Support\InviteToken;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    foreach (['super-admin', 'branch-head', 'branch-admin', 'member'] as $role) {
        Role::findOrCreate($role, 'web');
    }
    $this->branchA = Branch::factory()->create();
    $this->branchB = Branch::factory()->create();
    $this->appA = JoinApplication::factory()->pending()->create(['branch_id' => $this->branchA->id, 'email' => 'new.member@example.test']);
    $this->appB = JoinApplication::factory()->pending()->create(['branch_id' => $this->branchB->id]);
    $this->headA = User::factory()->branchHead($this->branchA)->create();
});

it('approves: creates a member in the right branch, links and stamps the application, sends set-password mail', function () {
    Notification::fake();

    $user = app(ApproveJoinApplication::class)->handle($this->appA, $this->headA);

    expect($user->email)->toBe('new.member@example.test')
        ->and($user->name)->toBe($this->appA->full_name)
        ->and($user->branch_id)->toBe($this->branchA->id)
        ->and($user->getRoleNames()->all())->toBe(['member'])
        ->and($user->hasAdminAccess())->toBeFalse()
        ->and($user->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();

    $app = $this->appA->fresh();
    expect($app->status)->toBe('approved')
        ->and($app->user_id)->toBe($user->id)
        ->and($app->reviewed_by)->toBe($this->headA->id)
        ->and($app->reviewed_at)->not->toBeNull();

    Notification::assertSentTo($user, WelcomeSetPassword::class);
});

it('set-password link works on the reset route', function () {
    $captured = null;
    Notification::fake();
    $user = app(ApproveJoinApplication::class)->handle($this->appA, $this->headA);
    Notification::assertSentTo($user, WelcomeSetPassword::class, function ($n) use (&$captured) {
        $captured = $n->token;

        return true;
    });

    expect(Password::broker('invites')->tokenExists($user, $captured))->toBeTrue();

    $this->post('/reset-password', [
        'token' => InviteToken::wrap($captured),
        'email' => $user->email,
        'password' => 'new-Password-123',
        'password_confirmation' => 'new-Password-123',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('new-Password-123', $user->fresh()->password))->toBeTrue();
});

it('rejects: sets status and reviewer, sends nothing', function () {
    Notification::fake();

    app(RejectJoinApplication::class)->handle($this->appA, $this->headA);

    $app = $this->appA->fresh();
    expect($app->status)->toBe('rejected')
        ->and($app->reviewed_by)->toBe($this->headA->id)
        ->and($app->reviewed_at)->not->toBeNull()
        ->and($app->user_id)->toBeNull();
    Notification::assertNothingSent();
});

it('refuses non-pending applications, even for super-admin', function () {
    $super = User::factory()->superAdmin()->create();
    $this->appA->forceFill(['status' => 'rejected'])->save();
    $this->appB->forceFill(['status' => 'approved'])->save();
    $before = User::count();

    expect(fn () => app(ApproveJoinApplication::class)->handle($this->appA, $super))->toThrow(JoinApplicationReviewException::class)
        ->and(fn () => app(RejectJoinApplication::class)->handle($this->appA, $super))->toThrow(JoinApplicationReviewException::class)
        ->and(fn () => app(ApproveJoinApplication::class)->handle($this->appB, $super))->toThrow(JoinApplicationReviewException::class)
        ->and(fn () => app(RejectJoinApplication::class)->handle($this->appB, $super))->toThrow(JoinApplicationReviewException::class);

    expect(User::count())->toBe($before);
});

it('double approve creates no second user', function () {
    Notification::fake();
    $stale = JoinApplication::find($this->appA->id);
    app(ApproveJoinApplication::class)->handle($this->appA, $this->headA);
    $count = User::count();

    expect(fn () => app(ApproveJoinApplication::class)->handle($stale, $this->headA))->toThrow(JoinApplicationReviewException::class);
    expect(User::count())->toBe($count);
});

it('refuses an existing user email and leaves the application pending', function () {
    Notification::fake();
    User::factory()->create(['email' => 'new.member@example.test']);
    $count = User::count();

    expect(fn () => app(ApproveJoinApplication::class)->handle($this->appA, $this->headA))->toThrow(JoinApplicationReviewException::class);

    expect($this->appA->fresh()->status)->toBe('pending')
        ->and(User::count())->toBe($count);
    Notification::assertNothingSent();
});

it('blocks a branch-head from another branch at service level', function () {
    $otherHead = User::factory()->branchHead($this->branchB)->create();
    $count = User::count();

    expect(fn () => app(ApproveJoinApplication::class)->handle($this->appA, $otherHead))->toThrow(AuthorizationException::class)
        ->and(fn () => app(RejectJoinApplication::class)->handle($this->appA, $otherHead))->toThrow(AuthorizationException::class);

    expect($this->appA->fresh()->status)->toBe('pending')->and(User::count())->toBe($count);
});

it('lets a branch-admin approve their own branch and blocks a member', function () {
    Notification::fake();
    $admin = User::factory()->branchAdmin($this->branchA)->create();
    $member = User::factory()->create(['branch_id' => $this->branchA->id]);
    $member->assignRole('member');

    expect(fn () => app(ApproveJoinApplication::class)->handle($this->appA, $member))->toThrow(AuthorizationException::class);

    app(ApproveJoinApplication::class)->handle($this->appA, $admin);
    expect($this->appA->fresh()->status)->toBe('approved');
});

it('filament: approve and reject work end to end from the view page', function () {
    Notification::fake();
    $this->actingAs($this->headA);

    Livewire::test(ViewJoinApplication::class, ['record' => $this->appA->getKey()])
        ->assertActionVisible('approve')
        ->callAction('approve')
        ->assertNotified('Application approved');

    expect($this->appA->fresh()->status)->toBe('approved');
    Notification::assertSentTo(User::where('email', 'new.member@example.test')->first(), WelcomeSetPassword::class);

    $other = JoinApplication::factory()->pending()->create(['branch_id' => $this->branchA->id]);
    Livewire::test(ViewJoinApplication::class, ['record' => $other->getKey()])
        ->callAction('reject')
        ->assertNotified('Application rejected');
    expect($other->fresh()->status)->toBe('rejected');
});

it('filament: actions are hidden once reviewed, even for super-admin', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $this->appA->forceFill(['status' => 'rejected'])->save();

    Livewire::test(ViewJoinApplication::class, ['record' => $this->appA->getKey()])
        ->assertActionHidden('approve')
        ->assertActionHidden('reject');
});

it('filament: other-branch head cannot see or call the actions', function () {
    $this->actingAs(User::factory()->branchHead($this->branchB)->create());

    Livewire::test(ListJoinApplications::class)
        ->assertCanNotSeeTableRecords([$this->appA]);

    $this->get(ViewJoinApplication::getUrl(['record' => $this->appA]))->assertNotFound();
    expect($this->appA->fresh()->status)->toBe('pending');
});

it('filament table row actions approve', function () {
    Notification::fake();
    $this->actingAs($this->headA);

    Livewire::test(ListJoinApplications::class)
        ->callTableAction('approve', $this->appA)
        ->assertNotified('Application approved');

    expect($this->appA->fresh()->status)->toBe('approved');
});

it('turns a unique-email race into a friendly failure and rolls back', function () {
    Notification::fake();
    $count = User::count();
    User::creating(function (User $user) {
        DB::table('users')->insert([
            'name' => 'Racer', 'email' => $user->email, 'password' => 'x',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    });

    expect(fn () => app(ApproveJoinApplication::class)->handle($this->appA, $this->headA))
        ->toThrow(JoinApplicationReviewException::class, 'already exists');

    expect($this->appA->fresh()->status)->toBe('pending')
        ->and(User::where('email', 'new.member@example.test')->count())->toBe(0)
        ->and(User::count())->toBe($count);
    Notification::assertNothingSent();
});

it('gives cross-branch staff the authorization failure even for reviewed applications', function () {
    $this->appA->forceFill(['status' => 'approved'])->save();
    $heads = [
        User::factory()->branchHead($this->branchB)->create(),
        User::factory()->branchAdmin($this->branchB)->create(),
    ];

    foreach ($heads as $staff) {
        expect(fn () => app(ApproveJoinApplication::class)->handle($this->appA, $staff))->toThrow(AuthorizationException::class)
            ->and(fn () => app(RejectJoinApplication::class)->handle($this->appA, $staff))->toThrow(AuthorizationException::class);
    }
});

it('normalises case and padding when checking for an existing account', function () {
    Notification::fake();
    $this->appA->forceFill(['email' => '  New.Member@Example.test '])->save();
    User::factory()->create(['email' => 'new.member@example.test']);
    $count = User::count();

    expect(fn () => app(ApproveJoinApplication::class)->handle($this->appA, $this->headA))->toThrow(JoinApplicationReviewException::class);
    expect($this->appA->fresh()->status)->toBe('pending')->and(User::count())->toBe($count);
});

it('stores a trimmed, lowercased email and name on the new user', function () {
    Notification::fake();
    $this->appA->forceFill(['email' => '  New.Member@Example.test ', 'full_name' => '  Juan Dela Cruz '])->save();

    $user = app(ApproveJoinApplication::class)->handle($this->appA, $this->headA);

    expect($user->email)->toBe('new.member@example.test')->and($user->name)->toBe('Juan Dela Cruz');
});

it('rolls everything back when a step after user creation fails', function () {
    Notification::fake();
    Role::where('name', 'member')->delete();
    $count = User::count();

    expect(fn () => app(ApproveJoinApplication::class)->handle($this->appA, $this->headA))->toThrow(RoleDoesNotExist::class);

    $app = $this->appA->fresh();
    expect(User::count())->toBe($count)
        ->and($app->status)->toBe('pending')
        ->and($app->reviewed_by)->toBeNull()
        ->and($app->reviewed_at)->toBeNull()
        ->and($app->user_id)->toBeNull();
    Notification::assertNothingSent();
});

it('filament table row action rejects', function () {
    Notification::fake();
    $this->actingAs($this->headA);

    Livewire::test(ListJoinApplications::class)
        ->callTableAction('reject', $this->appA)
        ->assertNotified('Application rejected');

    $app = $this->appA->fresh();
    expect($app->status)->toBe('rejected')->and($app->reviewed_by)->toBe($this->headA->id)->and($app->reviewed_at)->not->toBeNull();
});

it('gives the new member only the member role and the application branch, not the reviewer branch', function () {
    Notification::fake();
    $super = User::factory()->superAdmin()->create(['branch_id' => $this->branchB->id]);

    $user = app(ApproveJoinApplication::class)->handle($this->appA, $super);

    expect($user->getRoleNames()->all())->toBe(['member'])->and($user->branch_id)->toBe($this->branchA->id);
});

it('lets a branch-admin reject their own branch application', function () {
    Notification::fake();
    $admin = User::factory()->branchAdmin($this->branchA)->create();

    app(RejectJoinApplication::class)->handle($this->appA, $admin);

    $app = $this->appA->fresh();
    expect($app->status)->toBe('rejected')->and($app->reviewed_by)->toBe($admin->id)->and($app->reviewed_at)->not->toBeNull();
});
