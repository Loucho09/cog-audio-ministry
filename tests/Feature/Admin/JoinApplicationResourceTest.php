<?php

use App\Filament\Resources\JoinApplications\JoinApplicationResource;
use App\Filament\Resources\JoinApplications\Pages\ListJoinApplications;
use App\Filament\Resources\JoinApplications\Pages\ViewJoinApplication;
use App\Models\Branch;
use App\Models\JoinApplication;
use App\Models\User;
use App\Notifications\NewJoinApplication;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    foreach (['super-admin', 'branch-head', 'branch-admin', 'member'] as $role) {
        Role::findOrCreate($role, 'web');
    }
    $this->branchA = Branch::factory()->create();
    $this->branchB = Branch::factory()->create();
    $this->appA = JoinApplication::factory()->create(['branch_id' => $this->branchA->id]);
    $this->appB = JoinApplication::factory()->create(['branch_id' => $this->branchB->id]);
});

it('shows every branch to a super-admin', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(ListJoinApplications::class)->assertCanSeeTableRecords([$this->appA, $this->appB]);
});

it('limits a branch-head to their own branch', function () {
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    Livewire::test(ListJoinApplications::class)
        ->assertCanSeeTableRecords([$this->appA])
        ->assertCanNotSeeTableRecords([$this->appB]);
});

it('limits a branch-admin to their own branch', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(ListJoinApplications::class)
        ->assertCanSeeTableRecords([$this->appA])
        ->assertCanNotSeeTableRecords([$this->appB]);
});

it('lets staff open their own branch application', function () {
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    Livewire::test(ViewJoinApplication::class, ['record' => $this->appA->id])->assertSuccessful();
});

it('refuses another branch view page', function () {
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    $this->get(JoinApplicationResource::getUrl('view', ['record' => $this->appB]))
        ->assertNotFound();
});

it('keeps members and guests out', function () {
    $member = User::factory()->member($this->branchA)->create();

    $this->actingAs($member)
        ->get(JoinApplicationResource::getUrl('index'))
        ->assertForbidden();
    $this->actingAs($member)
        ->get(JoinApplicationResource::getUrl('view', ['record' => $this->appA]))
        ->assertForbidden();

    auth()->logout();
    $this->get(JoinApplicationResource::getUrl('index'))->assertRedirect();
});

it('filters by status', function () {
    $approved = JoinApplication::factory()->approved()->create(['branch_id' => $this->branchA->id]);
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(ListJoinApplications::class)
        ->assertCanSeeTableRecords([$this->appA])
        ->assertCanNotSeeTableRecords([$approved])
        ->filterTable('status', 'approved')
        ->assertCanSeeTableRecords([$approved])
        ->assertCanNotSeeTableRecords([$this->appA]);
});

it('filters by branch for super-admin and hides that filter from branch staff', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    Livewire::test(ListJoinApplications::class)
        ->assertTableFilterVisible('branch_id')
        ->filterTable('branch_id', $this->branchB->id)
        ->assertCanSeeTableRecords([$this->appB])
        ->assertCanNotSeeTableRecords([$this->appA]);

    $this->actingAs(User::factory()->branchHead($this->branchA)->create());
    Livewire::test(ListJoinApplications::class)->assertTableFilterHidden('branch_id');
});

it('searches by name and email', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $this->appA->update(['full_name' => 'Zed Unique']);

    Livewire::test(ListJoinApplications::class)
        ->searchTable('Zed Unique')
        ->assertCanSeeTableRecords([$this->appA])
        ->assertCanNotSeeTableRecords([$this->appB])
        ->searchTable($this->appB->email)
        ->assertCanSeeTableRecords([$this->appB])
        ->assertCanNotSeeTableRecords([$this->appA]);
});

it('has no create, edit or delete pages or actions', function () {
    expect(array_keys(JoinApplicationResource::getPages()))->toBe(['index', 'view']);

    $this->actingAs(User::factory()->superAdmin()->create());
    $this->get('/admin/join-applications/create')->assertNotFound();
    $this->get('/admin/join-applications/'.$this->appA->id.'/edit')->assertNotFound();

    Livewire::test(ListJoinApplications::class)
        ->assertActionDoesNotExist('create')
        ->assertTableActionDoesNotExist('edit', record: $this->appA)
        ->assertTableActionDoesNotExist('delete', record: $this->appA);
});

it('counts only own-branch pending applications in the nav badge', function () {
    JoinApplication::factory()->approved()->create(['branch_id' => $this->branchA->id]);

    $this->actingAs(User::factory()->branchHead($this->branchA)->create());
    expect(JoinApplicationResource::getNavigationBadge())->toBe('1');

    $this->actingAs(User::factory()->superAdmin()->create());
    expect(JoinApplicationResource::getNavigationBadge())->toBe('2');
});

it('links the notification to the resource view page', function () {
    $url = JoinApplicationResource::getUrl('view', ['record' => $this->appA], panel: 'admin');
    $html = (string) (new NewJoinApplication($this->appA))->toMail(new User)->render();

    expect($url)->toContain('/admin/join-applications/'.$this->appA->id)
        ->and($html)->toContain($url);
});

it('is not globally searchable', function () {
    expect(JoinApplicationResource::canGloballySearch())->toBeFalse();
});

it('refuses every write ability even for super-admin', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    expect(JoinApplicationResource::canCreate())->toBeFalse()
        ->and(JoinApplicationResource::canEdit($this->appA))->toBeFalse()
        ->and(JoinApplicationResource::canDelete($this->appA))->toBeFalse()
        ->and(JoinApplicationResource::canDeleteAny())->toBeFalse();
});

it('returns no nav badge without a user', function () {
    expect(JoinApplicationResource::getNavigationBadge())->toBeNull();
});

it('builds an absolute admin url from the configured app url outside a request', function () {
    config(['app.url' => 'https://example.test']);
    URL::forceRootUrl('https://example.test');
    URL::forceScheme('https');

    $html = (string) (new NewJoinApplication($this->appA))->toMail(new User)->render();

    expect($html)->toContain('https://example.test/admin/join-applications/'.$this->appA->id);
    URL::forceRootUrl(null);
    URL::forceScheme(null);
});

it('gives a branch-admin 404 on another branch view page', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    $this->get(JoinApplicationResource::getUrl('view', ['record' => $this->appB]))->assertNotFound();
});

it('shows nothing to staff without a branch', function () {
    foreach (['branchHead', 'branchAdmin'] as $state) {
        $user = User::factory()->$state($this->branchA)->create(['branch_id' => null]);
        $this->actingAs($user);

        expect(JoinApplicationResource::getEloquentQuery()->count())->toBe(0);
        $this->get(JoinApplicationResource::getUrl('index'))->assertForbidden();
        $this->get(JoinApplicationResource::getUrl('view', ['record' => $this->appA]))->assertForbidden();
    }
});

it('lets super-admin open any branch view page', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    foreach ([$this->appA, $this->appB] as $app) {
        Livewire::test(ViewJoinApplication::class, ['record' => $app->id])->assertSuccessful();
    }
});

it('escapes applicant text on the view page', function () {
    $app = JoinApplication::factory()->create([
        'branch_id' => $this->branchA->id,
        'full_name' => '<script>alert(1)</script>',
        'contact_number' => '<script>alert(2)</script>',
    ]);
    $this->actingAs(User::factory()->superAdmin()->create());

    $this->get(JoinApplicationResource::getUrl('view', ['record' => $app]))
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('<script>alert(2)</script>', false)
        ->assertSee('&lt;script&gt;alert(1)', false);
});

it('renders a non-https facebook url without a link', function () {
    $app = JoinApplication::factory()->create([
        'branch_id' => $this->branchA->id,
        'facebook_url' => 'javascript:alert(1)',
    ]);
    $this->actingAs(User::factory()->superAdmin()->create());

    $this->get(JoinApplicationResource::getUrl('view', ['record' => $app]))
        ->assertOk()
        ->assertDontSee('href="javascript:', false);
});

it('has no bulk actions', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $component = Livewire::test(ListJoinApplications::class);

    expect($component->instance()->getTable()->getBulkActions())->toBeEmpty();
});
