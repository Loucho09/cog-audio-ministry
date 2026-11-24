<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Branch;
use App\Models\User;
use App\Notifications\WelcomeSetPassword;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
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

it('lets a super-admin create a user with role, branch and a set-password email', function () {
    Notification::fake();
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'New Person',
            'email' => 'new@example.test',
            'branch_id' => $this->branchA->id,
            'role' => 'branch-admin',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'new@example.test')->firstOrFail();
    expect($user->branch_id)->toBe($this->branchA->id)
        ->and($user->hasRole('branch-admin'))->toBeTrue()
        ->and(Hash::check('password', $user->password))->toBeFalse();

    Notification::assertSentTo($user, WelcomeSetPassword::class);
});

it('defaults the new user role to member', function () {
    Notification::fake();
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'Plain', 'email' => 'plain@example.test'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::where('email', 'plain@example.test')->firstOrFail()->hasRole('member'))->toBeTrue();
});

it('shows a form error and creates nothing for a branch role without a branch', function () {
    Notification::fake();
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'X', 'email' => 'x@example.test', 'role' => 'branch-head'])
        ->call('create')
        ->assertHasFormErrors(['role']);

    expect(User::where('email', 'x@example.test')->exists())->toBeFalse();
    Notification::assertNothingSent();
});

it('rejects a duplicate email on create', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    User::factory()->create(['email' => 'dup@example.test']);

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'D', 'email' => 'dup@example.test'])
        ->call('create')
        ->assertHasFormErrors(['email']);
});

it('lets a super-admin see all users', function () {
    $a = User::factory()->member($this->branchA)->create();
    $b = User::factory()->member($this->branchB)->create();
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(ListUsers::class)->assertCanSeeTableRecords([$a, $b]);
});

it('changes a role through the form via AssignRole', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $target = User::factory()->member($this->branchA)->create();

    Livewire::test(EditUser::class, ['record' => $target->id])
        ->assertFormSet(['role' => 'member'])
        ->fillForm(['role' => 'branch-admin'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($target->fresh()->hasRole('branch-admin'))->toBeTrue()
        ->and($target->fresh()->hasRole('member'))->toBeFalse();
});

it('shows a form error, not a 500, for branch-head on a user with no branch', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $target = User::factory()->member($this->branchA)->create(['branch_id' => null]);

    Livewire::test(EditUser::class, ['record' => $target->id])
        ->fillForm(['role' => 'branch-head', 'name' => 'Changed'])
        ->call('save')
        ->assertHasFormErrors(['role']);

    expect($target->fresh()->hasRole('member'))->toBeTrue()
        ->and($target->fresh()->name)->not->toBe('Changed');
});

it('lets a super-admin move a user to another branch', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $target = User::factory()->member($this->branchA)->create();

    Livewire::test(EditUser::class, ['record' => $target->id])
        ->fillForm(['branch_id' => $this->branchB->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($target->fresh()->branch_id)->toBe($this->branchB->id);
});

it('cannot demote the last super-admin through the form', function () {
    $admin = User::factory()->superAdmin()->create();
    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $admin->id])
        ->fillForm(['role' => 'member', 'name' => 'Renamed'])
        ->call('save')
        ->assertNotified('Not allowed');

    expect($admin->fresh()->hasRole('super-admin'))->toBeTrue()
        ->and($admin->fresh()->name)->not->toBe('Renamed');
});

it('limits a branch-head to own-branch users', function () {
    $head = User::factory()->branchHead($this->branchA)->create();
    $mine = User::factory()->member($this->branchA)->create();
    $other = User::factory()->member($this->branchB)->create();
    $this->actingAs($head);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$other]);

    $this->get("/admin/users/{$other->id}/edit")->assertNotFound();
    $this->get('/admin/users/create')->assertForbidden();
});

it('lets a branch-head edit a member name but ignores crafted role and branch', function () {
    $head = User::factory()->branchHead($this->branchA)->create();
    $member = User::factory()->member($this->branchA)->create();
    $this->actingAs($head);

    Livewire::test(EditUser::class, ['record' => $member->id])
        ->fillForm(['name' => 'Better Name'])
        ->set('data.role', 'super-admin')
        ->set('data.branch_id', $this->branchB->id)
        ->call('save')
        ->assertHasNoFormErrors();

    $member->refresh();
    expect($member->name)->toBe('Better Name')
        ->and($member->hasRole('member'))->toBeTrue()
        ->and($member->hasRole('super-admin'))->toBeFalse()
        ->and($member->branch_id)->toBe($this->branchA->id);
});

it('forbids a branch-head from editing another admin', function () {
    $head = User::factory()->branchHead($this->branchA)->create();
    $otherAdmin = User::factory()->branchAdmin($this->branchA)->create();
    $this->actingAs($head);

    $this->get("/admin/users/{$otherAdmin->id}/edit")->assertForbidden();
});

it('forbids a branch-admin from editing but lists own branch', function () {
    $admin = User::factory()->branchAdmin($this->branchA)->create();
    $member = User::factory()->member($this->branchA)->create();
    $this->actingAs($admin);

    Livewire::test(ListUsers::class)->assertCanSeeTableRecords([$member]);
    $this->get("/admin/users/{$member->id}/edit")->assertForbidden();
    $this->get('/admin/users/create')->assertForbidden();
});

it('forbids members from the users page', function () {
    $this->actingAs(User::factory()->member($this->branchA)->create());

    $this->get('/admin/users')->assertForbidden();
});

it('bulk delete by a super-admin skips self', function () {
    $me = User::factory()->superAdmin()->create();
    $victim = User::factory()->member($this->branchA)->create();
    $this->actingAs($me);

    Livewire::test(ListUsers::class)
        ->selectTableRecords([$me->id, $victim->id])
        ->callAction(TestAction::make('delete')->table()->bulk());

    expect(User::find($me->id))->not->toBeNull()
        ->and(User::find($victim->id))->toBeNull();
});

it('blocks deleting self but allows deleting another super-admin', function () {
    $me = User::factory()->superAdmin()->create();
    $other = User::factory()->superAdmin()->create();
    $this->actingAs($me);

    Livewire::test(EditUser::class, ['record' => $me->id])->callAction('delete');
    expect(User::find($me->id))->not->toBeNull();

    Livewire::test(EditUser::class, ['record' => $other->id])->callAction('delete');
    expect(User::find($other->id))->toBeNull();
});

it('hides bulk delete from a branch-head', function () {
    $head = User::factory()->branchHead($this->branchA)->create();
    $member = User::factory()->member($this->branchA)->create();
    $this->actingAs($head);

    Livewire::test(ListUsers::class)
        ->selectTableRecords([$member->id])
        ->assertActionHidden(TestAction::make('delete')->table()->bulk());

    expect(User::find($member->id))->not->toBeNull();
});

it('denies delete to a branch-head by policy', function () {
    $head = User::factory()->branchHead($this->branchA)->create();
    $member = User::factory()->member($this->branchA)->create();

    expect($head->can('delete', $member))->toBeFalse();
});

it('hides and rejects the delete action for a branch-head', function () {
    $head = User::factory()->branchHead($this->branchA)->create();
    $member = User::factory()->member($this->branchA)->create();
    $this->actingAs($head);

    Livewire::test(EditUser::class, ['record' => $member->id])->assertActionHidden('delete');

    expect(User::find($member->id))->not->toBeNull();
});

it('keeps at least one super-admin on bulk delete', function () {
    $me = User::factory()->superAdmin()->create();
    $other = User::factory()->superAdmin()->create();
    $this->actingAs($me);

    Livewire::test(ListUsers::class)
        ->selectTableRecords([$me->id, $other->id])
        ->callAction(TestAction::make('delete')->table()->bulk());

    expect(User::role('super-admin')->count())->toBeGreaterThanOrEqual(1)
        ->and(User::find($me->id))->not->toBeNull();
});

it('ignores a submitted email from a branch-head but saves the name', function () {
    $head = User::factory()->branchHead($this->branchA)->create();
    $member = User::factory()->member($this->branchA)->create(['email' => 'keep@example.test']);
    $this->actingAs($head);

    Livewire::test(EditUser::class, ['record' => $member->id])
        ->fillForm(['name' => 'New Name'])
        ->set('data.email', 'changed@example.test')
        ->call('save');

    expect($member->fresh()->email)->toBe('keep@example.test')
        ->and($member->fresh()->name)->toBe('New Name');
});

it('creates the user with a warning when the mail broker throws', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    Password::shouldReceive('broker')->andThrow(new RuntimeException('smtp down'));

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'Mail Fail', 'email' => 'fail@example.test'])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified('The set-password email could not be sent');

    expect(User::where('email', 'fail@example.test')->exists())->toBeTrue();
});

it('lets a super-admin resend the set-password email', function () {
    Notification::fake();
    $this->actingAs(User::factory()->superAdmin()->create());
    $target = User::factory()->member($this->branchA)->create();

    Livewire::test(EditUser::class, ['record' => $target->id])->callAction('resendSetPassword');
    Notification::assertSentTo($target, WelcomeSetPassword::class);
});

it('hides and rejects resend for a branch-head', function () {
    Notification::fake();
    $head = User::factory()->branchHead($this->branchA)->create();
    $member = User::factory()->member($this->branchA)->create();
    $this->actingAs($head);

    Livewire::test(EditUser::class, ['record' => $member->id])
        ->assertActionHidden('resendSetPassword');
    Livewire::test(ListUsers::class)
        ->assertActionHidden(TestAction::make('resendSetPassword')->table($member));

    Notification::assertNothingSent();
});

it('rejects a forged non-existent branch id', function () {
    Notification::fake();
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'F', 'email' => 'forged@example.test', 'branch_id' => 999999])
        ->call('create')
        ->assertHasFormErrors(['branch_id']);

    expect(User::where('email', 'forged@example.test')->exists())->toBeFalse();
});

it('rejects branch-head and branch-admin calling CreateUser directly', function () {
    foreach ([User::factory()->branchHead($this->branchA)->create(), User::factory()->branchAdmin($this->branchA)->create()] as $actor) {
        $this->actingAs($actor);
        Livewire::test(CreateUser::class)->assertForbidden();
    }
});
