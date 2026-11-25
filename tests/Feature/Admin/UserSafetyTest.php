<?php

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\AccountsAttentionWidget;
use App\Filament\Widgets\BranchStatsWidget;
use App\Filament\Widgets\RecentAccountsWidget;
use App\Models\Branch;
use App\Models\DutyAssignment;
use App\Models\Event;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    foreach (['super-admin', 'branch-head', 'branch-admin', 'member'] as $role) {
        Role::findOrCreate($role, 'web');
    }
    $this->a = Branch::factory()->create();
    $this->b = Branch::factory()->create();
});

it('blocks moving a user with upcoming duties but allows it for past duties', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $member = User::factory()->member($this->a)->create();
    $event = Event::factory()->create([
        'branch_id' => $this->a->id,
        'starts_at' => now()->addDays(3),
        'ends_at' => now()->addDays(3)->addHours(2),
    ]);
    DutyAssignment::factory()->create(['event_id' => $event->id, 'user_id' => $member->id]);

    Livewire::test(EditUser::class, ['record' => $member->id])
        ->fillForm(['branch_id' => $this->b->id])
        ->call('save')
        ->assertNotified('Cannot move this user');

    expect($member->fresh()->branch_id)->toBe($this->a->id);

    $event->forceFill(['starts_at' => now()->subDays(2), 'ends_at' => now()->subDays(2)->addHours(2)])->save();

    Livewire::test(EditUser::class, ['record' => $member->id])
        ->fillForm(['branch_id' => $this->b->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($member->fresh()->branch_id)->toBe($this->b->id);
});

it('stops a super-admin changing their own role or branch', function () {
    $me = User::factory()->superAdmin()->create(['branch_id' => null]);
    $this->actingAs($me);

    Livewire::test(EditUser::class, ['record' => $me->id])
        ->assertFormFieldIsDisabled('role')
        ->assertFormFieldIsDisabled('branch_id');

    // Server-side: even forged state is ignored.
    Livewire::test(EditUser::class, ['record' => $me->id])
        ->set('data.role', 'member')
        ->set('data.branch_id', $this->a->id)
        ->call('save');

    $me->refresh();
    expect($me->hasRole('super-admin'))->toBeTrue()->and($me->branch_id)->toBeNull();
});

it('uses one needs-attention rule for the tab and the widget', function () {
    $super = User::factory()->superAdmin()->create(['branch_id' => null, 'must_change_password' => false]);
    User::factory()->member($this->a)->create(['must_change_password' => true]);
    User::factory()->member($this->a)->unverified()->create(['must_change_password' => false]);
    User::factory()->create(['branch_id' => null, 'must_change_password' => false]);
    User::factory()->member($this->a)->create(['must_change_password' => false]);

    $this->actingAs($super);

    $expected = UserResource::getEloquentQuery()->needsAttention(true)->count();
    expect($expected)->toBe(3);

    $widget = Livewire::test(AccountsAttentionWidget::class)
        ->assertSee('Invite not accepted yet')
        ->assertSee('Still on temporary password');
    expect($widget->instance()->getTableRecords())->toHaveCount($expected);

    $list = Livewire::test(ListUsers::class)->set('activeTab', 'attention');
    expect($list->instance()->getTableRecords())->toHaveCount($expected);
});

it('logs temporary password sets without the password', function () {
    Log::spy();
    $admin = User::factory()->superAdmin()->create();
    $target = User::factory()->member($this->a)->create();
    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $target->id])
        ->callAction(TestAction::make('setTemporaryPassword'), ['password' => 'Str0ng-Temp-Pass!9'])
        ->assertHasNoFormErrors();

    Log::shouldHaveReceived('info')->withArgs(fn ($msg, $ctx) => $msg === 'Temporary password set'
        && $ctx === ['actor_id' => $admin->id, 'target_id' => $target->id])->once();
});

it('gives members no user data and no widget access', function () {
    $member = User::factory()->member($this->a)->create();
    User::factory()->member($this->a)->create();
    $this->actingAs($member);

    expect(UserResource::getEloquentQuery()->count())->toBe(0);

    foreach ([AccountsAttentionWidget::class, RecentAccountsWidget::class, BranchStatsWidget::class] as $widget) {
        expect($widget::canView())->toBeFalse();
        Livewire::test($widget)->assertForbidden();
    }

    $this->get('/admin')->assertForbidden();
});

it('lets a branch-admin see only own-branch widgets and users', function () {
    $mine = User::factory()->member($this->a)->create(['must_change_password' => true]);
    $other = User::factory()->member($this->b)->create(['must_change_password' => true]);
    $this->actingAs(User::factory()->branchAdmin($this->a)->create());

    foreach ([AccountsAttentionWidget::class, RecentAccountsWidget::class, BranchStatsWidget::class] as $widget) {
        expect($widget::canView())->toBeTrue();
    }

    Livewire::test(AccountsAttentionWidget::class)
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$other]);
});

it('hides another branch\'s user edit page from a branch-admin', function () {
    $other = User::factory()->member($this->b)->create();
    $this->actingAs(User::factory()->branchAdmin($this->a)->create());

    $this->get("/admin/users/{$other->id}/edit")->assertNotFound();
});
