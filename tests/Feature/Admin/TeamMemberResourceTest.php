<?php

use App\Filament\Resources\TeamMembers\Pages\CreateTeamMember;
use App\Filament\Resources\TeamMembers\Pages\EditTeamMember;
use App\Filament\Resources\TeamMembers\Pages\ListTeamMembers;
use App\Models\Branch;
use App\Models\TeamMember;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    Storage::fake('public');
    $this->branchA = Branch::factory()->create();
    $this->branchB = Branch::factory()->create();
    $this->memberA = TeamMember::factory()->create(['branch_id' => $this->branchA->id, 'name' => 'Alpha Person']);
    $this->memberB = TeamMember::factory()->create(['branch_id' => $this->branchB->id, 'name' => 'Bravo Person']);
    $this->memberGlobal = TeamMember::factory()->create(['branch_id' => null, 'name' => 'Global Person']);
});

it('lets a super-admin see every record', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(ListTeamMembers::class)
        ->assertCanSeeTableRecords([$this->memberA, $this->memberB, $this->memberGlobal]);
});

it('lets a super-admin create a global member with a photo', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(CreateTeamMember::class)
        ->fillForm([
            'branch_id' => null,
            'name' => 'Test Global',
            'photo_path' => UploadedFile::fake()->image('me.webp'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $member = TeamMember::where('name', 'Test Global')->firstOrFail();
    expect($member->branch_id)->toBeNull()->and($member->photo_path)->toStartWith('team/');
    Storage::disk('public')->assertExists($member->photo_path);
});

it('shows a branch-head own and global records but not other branches', function () {
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    Livewire::test(ListTeamMembers::class)
        ->assertCanSeeTableRecords([$this->memberA, $this->memberGlobal])
        ->assertCanNotSeeTableRecords([$this->memberB]);
});

it('forces a branch-head new member into their own branch', function () {
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    Livewire::test(CreateTeamMember::class)
        ->fillForm(['name' => 'Forced', 'branch_id' => $this->branchB->id])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(TeamMember::where('name', 'Forced')->firstOrFail()->branch_id)->toBe($this->branchA->id);
});

it('returns 403 for a branch-head editing a global record and 404 for another branch', function () {
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    $this->get("/admin/team-members/{$this->memberGlobal->id}/edit")->assertForbidden();
    $this->get("/admin/team-members/{$this->memberB->id}/edit")->assertNotFound();
});

it('keeps the existing photo path when editing without re-uploading', function () {
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());
    $this->memberA->update(['photo_path' => 'images/placeholders/team-1.svg']);

    Livewire::test(EditTeamMember::class, ['record' => $this->memberA->id])
        ->fillForm(['role_title' => 'Sound lead'])
        ->call('save')
        ->assertHasNoFormErrors();

    $fresh = $this->memberA->fresh();
    expect($fresh->role_title)->toBe('Sound lead')
        ->and($fresh->photo_path)->toBe('images/placeholders/team-1.svg');
});

it('rejects a non-image upload', function () {
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    Livewire::test(CreateTeamMember::class)
        ->fillForm([
            'name' => 'Bad Upload',
            'photo_path' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ])
        ->call('create')
        ->assertHasFormErrors(['photo_path']);

    expect(TeamMember::where('name', 'Bad Upload')->exists())->toBeFalse();
});

it('forbids members from the team page', function () {
    $this->actingAs(User::factory()->member($this->branchA)->create());

    $this->get('/admin/team-members')->assertForbidden();
});
