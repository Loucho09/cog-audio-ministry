<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\Branch;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    foreach (['super-admin', 'branch-head', 'branch-admin', 'member'] as $role) {
        Role::findOrCreate($role, 'web');
    }
    $this->branchA = Branch::factory()->create();
    $this->branchB = Branch::factory()->create();
});

it('creates a user with a temporary password, no email, and the user can log in', function () {
    Notification::fake();
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Temp Person',
            'email' => 'temp@example.test',
            'branch_id' => $this->branchA->id,
            'role' => 'member',
            'password_mode' => 'temporary',
            'password' => 'Temp-Pass-12345',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'temp@example.test')->firstOrFail();
    expect($user->must_change_password)->toBeTrue()
        ->and(Hash::check('Temp-Pass-12345', $user->password))->toBeTrue();

    Notification::assertNotSentTo($user, ResetPassword::class);

    auth()->logout();
    $this->post('/login', ['email' => 'temp@example.test', 'password' => 'Temp-Pass-12345']);
    $this->assertAuthenticatedAs($user);
});

it('requires a password when the temporary option is chosen', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'No Pass',
            'email' => 'nopass@example.test',
            'password_mode' => 'temporary',
        ])
        ->call('create')
        ->assertHasFormErrors(['password' => 'required']);

    expect(User::where('email', 'nopass@example.test')->exists())->toBeFalse();
});

it('keeps sending the set-password email for the email option', function () {
    Notification::fake();
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'Mail Person', 'email' => 'mail@example.test', 'password_mode' => 'email'])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'mail@example.test')->firstOrFail();
    expect($user->must_change_password)->toBeFalse();
    Notification::assertSentTo($user, ResetPassword::class);
});

it('lets a super-admin set a temporary password on a user and clears their sessions', function () {
    Notification::fake();
    config(['session.driver' => 'database']);
    $this->actingAs(User::factory()->superAdmin()->create());
    $target = User::factory()->member($this->branchA)->create();
    $other = User::factory()->member($this->branchA)->create();
    foreach ([$target, $other] as $u) {
        DB::table('sessions')->insert([
            'id' => 'sess-'.$u->id, 'user_id' => $u->id, 'payload' => '', 'last_activity' => time(),
        ]);
    }
    $oldToken = $target->remember_token;

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('setTemporaryPassword')->table($target), ['password' => 'Temp-Pass-12345'])
        ->assertHasNoFormErrors();

    $target->refresh();
    expect($target->must_change_password)->toBeTrue()
        ->and(Hash::check('Temp-Pass-12345', $target->password))->toBeTrue()
        ->and($target->remember_token)->not->toBe($oldToken)
        ->and(DB::table('sessions')->where('user_id', $target->id)->count())->toBe(0)
        ->and(DB::table('sessions')->where('user_id', $other->id)->count())->toBe(1);
    Notification::assertNothingSent();
});

it('lets a branch-head set a temporary password on an own-branch member', function () {
    $head = User::factory()->branchHead($this->branchA)->create();
    $member = User::factory()->member($this->branchA)->create();
    $this->actingAs($head);

    expect($head->can('setTemporaryPassword', $member))->toBeTrue();

    Livewire::test(EditUser::class, ['record' => $member->id])
        ->callAction('setTemporaryPassword', ['password' => 'Temp-Pass-12345']);

    expect($member->refresh()->must_change_password)->toBeTrue();
});

it('denies a branch-head on other branches, branch-admins and themselves', function () {
    $head = User::factory()->branchHead($this->branchA)->create();
    $foreign = User::factory()->member($this->branchB)->create();
    $admin = User::factory()->branchAdmin($this->branchA)->create();
    $this->actingAs($head);

    foreach ([$foreign, $admin, $head] as $target) {
        expect($head->can('setTemporaryPassword', $target))->toBeFalse();
    }

    expect(UserResource::canSetTemporaryPassword($head))->toBeFalse();

    Livewire::test(ListUsers::class)
        ->assertActionHidden(TestAction::make('setTemporaryPassword')->table($admin));

    expect($admin->refresh()->must_change_password)->toBeFalse();
});

it('does not let a super-admin use it on themselves', function () {
    $me = User::factory()->superAdmin()->create();
    $this->actingAs($me);

    Livewire::test(EditUser::class, ['record' => $me->id])
        ->assertActionHidden('setTemporaryPassword');
});

it('denies a branch-admin and a member', function () {
    $member = User::factory()->member($this->branchA)->create();

    foreach ([User::factory()->branchAdmin($this->branchA)->create(), User::factory()->member($this->branchA)->create()] as $actor) {
        expect($actor->can('setTemporaryPassword', $member))->toBeFalse();
    }
});
