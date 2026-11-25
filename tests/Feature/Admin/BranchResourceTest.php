<?php

use App\Filament\Resources\Branches\BranchResource;
use App\Filament\Resources\Branches\Pages\CreateBranch;
use App\Filament\Resources\Branches\Pages\EditBranch;
use App\Filament\Resources\Branches\Pages\ListBranches;
use App\Models\Branch;
use App\Models\DutyAssignment;
use App\Models\DutyRole;
use App\Models\Event;
use App\Models\GalleryImage;
use App\Models\JoinApplication;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    $this->main = Branch::factory()->create(['is_main' => true]);
    $this->other = Branch::factory()->create();
});

it('lets a super-admin create a branch', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(CreateBranch::class)
        ->fillForm(['name' => 'COG New Place', 'slug' => 'cog-new-place', 'facebook_url' => 'https://www.facebook.com/new'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Branch::where('slug', 'cog-new-place')->firstOrFail()->facebook_url)->toBe('https://www.facebook.com/new');
});

it('rejects a facebook_url that is not a URL', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(EditBranch::class, ['record' => $this->other->id])
        ->fillForm(['facebook_url' => 'not a url'])
        ->call('save')
        ->assertHasFormErrors(['facebook_url']);
});

it('keeps exactly one main branch after save', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(EditBranch::class, ['record' => $this->other->id])
        ->fillForm(['is_main' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Branch::where('is_main', true)->pluck('id')->all())->toBe([$this->other->id]);
});

it('refuses to unset the only main branch', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(EditBranch::class, ['record' => $this->main->id])
        ->fillForm(['is_main' => false])
        ->call('save')
        ->assertHasFormErrors(['is_main']);

    expect($this->main->fresh()->is_main)->toBeTrue();
});

it('does not delete a branch that has users', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    User::factory()->member($this->other)->create();

    Livewire::test(EditBranch::class, ['record' => $this->other->id])->callAction('delete');

    expect(Branch::find($this->other->id))->not->toBeNull();
});

it('does not delete a branch that has services', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    Service::factory()->create(['branch_id' => $this->other->id]);

    Livewire::test(EditBranch::class, ['record' => $this->other->id])->callAction('delete');

    expect(Branch::find($this->other->id))->not->toBeNull();
});

it('does not delete a branch that has gallery photos or team members', function (string $model) {
    $this->actingAs(User::factory()->superAdmin()->create());
    $model::factory()->create(['branch_id' => $this->other->id]);

    Livewire::test(EditBranch::class, ['record' => $this->other->id])->callAction('delete');

    expect(Branch::find($this->other->id))->not->toBeNull();
})->with([GalleryImage::class, TeamMember::class]);

it('deletes an empty non-main branch', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(EditBranch::class, ['record' => $this->other->id])->callAction('delete');

    expect(Branch::find($this->other->id))->toBeNull();
});

it('shows a branch-admin only their own branch, read-only', function () {
    $this->actingAs(User::factory()->branchAdmin($this->other)->create());

    Livewire::test(ListBranches::class)
        ->assertCanSeeTableRecords([$this->other])
        ->assertCanNotSeeTableRecords([$this->main]);

    $this->get('/admin/branches/create')->assertForbidden();
    $this->get("/admin/branches/{$this->other->id}/edit")->assertForbidden();
    $this->get("/admin/branches/{$this->main->id}/edit")->assertNotFound();
});

it('forbids members from the branches page', function () {
    $this->actingAs(User::factory()->member($this->other)->create());

    $this->get('/admin/branches')->assertForbidden();
});

it('rejects a javascript: or http facebook_url', function (string $bad) {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(EditBranch::class, ['record' => $this->other->id])
        ->fillForm(['facebook_url' => $bad])
        ->call('save')
        ->assertHasFormErrors(['facebook_url']);
})->with(['javascript:alert(1)', 'http://www.facebook.com/x']);

it('lists branches with member and service counts for the super-admin', function () {
    User::factory()->member($this->other)->create();
    Service::factory()->create(['branch_id' => $this->other->id]);
    $this->actingAs(User::factory()->superAdmin()->create());

    $test = Livewire::test(ListBranches::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$this->main, $this->other]);

    $row = $test->instance()->getTableRecords()->firstWhere('id', $this->other->id);
    expect($row->users_count)->toBe(1)->and($row->services_count)->toBe(1);
});

it('lists only their own branch for a branch-admin', function () {
    $this->actingAs(User::factory()->branchAdmin($this->other)->create());

    Livewire::test(ListBranches::class)
        ->assertCanSeeTableRecords([$this->other])
        ->assertCanNotSeeTableRecords([$this->main]);
});

it('renders the edit page summary card', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(EditBranch::class, ['record' => $this->main->id])
        ->assertSuccessful()
        ->assertSee('Summary')
        ->assertSee('Main branch');
});

it('does not delete the main branch from the edit page', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(EditBranch::class, ['record' => $this->main->id])->callAction('delete');

    expect(Branch::find($this->main->id))->not->toBeNull();
});

it('does not delete a branch that has events and says why', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    Event::factory()->create(['branch_id' => $this->other->id]);

    Livewire::test(EditBranch::class, ['record' => $this->other->id])
        ->callAction('delete')
        ->assertNotified('Cannot delete');

    expect(Branch::find($this->other->id))->not->toBeNull();
});

it('does not delete a branch with join applications, duty roles or duty assignments and says why', function (string $kind, string $message) {
    $this->actingAs(User::factory()->superAdmin()->create());

    match ($kind) {
        'applications' => JoinApplication::factory()->create(['branch_id' => $this->other->id]),
        'roles' => DutyRole::factory()->create(['branch_id' => $this->other->id]),
        'assignments' => DutyAssignment::factory()->create([
            'event_id' => Event::factory()->create(['branch_id' => $this->other->id])->id,
        ]),
    };

    // Single delete.
    Livewire::test(EditBranch::class, ['record' => $this->other->id])
        ->callAction('delete')
        ->assertNotified('Cannot delete');
    expect(Branch::find($this->other->id))->not->toBeNull();

    // Bulk delete.
    Livewire::test(ListBranches::class)
        ->selectTableRecords([$this->other->id])
        ->callAction(TestAction::make('delete')->table()->bulk());
    expect(Branch::find($this->other->id))->not->toBeNull()
        ->and(BranchResource::deleteBlocker($this->other))->toContain($message);
})->with([
    ['applications', 'still has join applications'],
    ['roles', 'still has duty roles'],
    ['assignments', 'still has events'],
]);

it('rejects a duplicate branch name', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(CreateBranch::class)
        ->fillForm(['name' => $this->other->name, 'slug' => 'something-else'])
        ->call('create')
        ->assertHasFormErrors(['name']);
});

it('keeps the slug read-only when editing', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $slug = $this->other->slug;

    Livewire::test(EditBranch::class, ['record' => $this->other->id])
        ->assertFormFieldIsDisabled('slug')
        ->fillForm(['name' => 'Renamed Branch', 'slug' => 'hacked'])
        ->call('save');

    expect($this->other->fresh()->slug)->toBe($slug);
});

it('hides the branches nav item from non-super-admins', function () {
    $this->actingAs(User::factory()->branchAdmin($this->other)->create());
    expect(BranchResource::shouldRegisterNavigation())->toBeFalse();

    $this->actingAs(User::factory()->superAdmin()->create());
    expect(BranchResource::shouldRegisterNavigation())->toBeTrue();
});
