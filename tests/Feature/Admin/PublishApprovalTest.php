<?php

use App\Filament\Resources\GalleryImages\Pages\CreateGalleryImage;
use App\Filament\Resources\GalleryImages\Pages\EditGalleryImage;
use App\Filament\Resources\TeamMembers\Pages\CreateTeamMember;
use App\Filament\Resources\TeamMembers\Pages\EditTeamMember;
use App\Filament\Resources\TeamMembers\Pages\ListTeamMembers;
use App\Models\Branch;
use App\Models\GalleryImage;
use App\Models\TeamMember;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    Storage::fake('public');
    $this->withoutVite();
    config(['ministry.show_team' => true]);
    $this->branch = Branch::factory()->create(['is_main' => true]);
    $this->head = User::factory()->branchHead($this->branch)->create();
    $this->admin = User::factory()->superAdmin()->create();
});

it('keeps a branch-head new team member unpublished and off the homepage until a super-admin publishes', function () {
    $this->actingAs($this->head);

    Livewire::test(CreateTeamMember::class)
        ->fillForm(['name' => 'Pending Person', 'is_featured' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    $member = TeamMember::where('name', 'Pending Person')->firstOrFail();
    expect($member->is_published)->toBeFalse();
    $this->get('/')->assertOk()->assertDontSee('Pending Person');

    $this->actingAs($this->admin);
    Livewire::test(EditTeamMember::class, ['record' => $member->id])
        ->assertFormSet(['is_published' => false])
        ->fillForm(['is_published' => true])
        ->call('save');

    expect($member->fresh()->is_published)->toBeTrue();
    $this->get('/')->assertSee('Pending Person');
});

it('publishes by default when a super-admin creates a team member', function () {
    $this->actingAs($this->admin);

    Livewire::test(CreateTeamMember::class)->fillForm(['name' => 'Direct Person'])->call('create');

    expect(TeamMember::where('name', 'Direct Person')->firstOrFail()->is_published)->toBeTrue();
});

it('hides the toggle and ignores a crafted publish payload from a branch-head', function () {
    $this->actingAs($this->head);

    Livewire::test(CreateTeamMember::class)
        ->assertFormFieldHidden('is_published')
        ->fillForm(['name' => 'Crafted'])
        ->set('data.is_published', true)
        ->call('create');

    expect(TeamMember::where('name', 'Crafted')->firstOrFail()->is_published)->toBeFalse();

    Livewire::test(CreateGalleryImage::class)
        ->assertFormFieldHidden('is_published')
        ->fillForm(['image' => null, 'alt_text' => 'Crafted photo', 'path' => UploadedFile::fake()->image('a.jpg')])
        ->set('data.is_published', true)
        ->call('create');

    expect(GalleryImage::where('alt_text', 'Crafted photo')->firstOrFail()->is_published)->toBeFalse();

    $existing = TeamMember::factory()->create(['branch_id' => $this->branch->id, 'is_published' => false]);
    Livewire::test(EditTeamMember::class, ['record' => $existing->id])
        ->set('data.is_published', true)
        ->call('save');

    expect($existing->fresh()->is_published)->toBeFalse();
});

it('puts a published item back to pending when a branch-head changes its content', function () {
    $member = TeamMember::factory()->create(['branch_id' => $this->branch->id, 'name' => 'Live Person']);
    $this->actingAs($this->head);

    Livewire::test(EditTeamMember::class, ['record' => $member->id])
        ->fillForm(['name' => 'Renamed Person'])
        ->call('save');

    expect($member->fresh()->is_published)->toBeFalse();
    $this->get('/')->assertDontSee('Renamed Person');
});

it('keeps a published item live when a branch-head changes only the sort order', function () {
    $member = TeamMember::factory()->create(['branch_id' => $this->branch->id, 'name' => 'Live Person']);
    $this->actingAs($this->head);

    Livewire::test(EditTeamMember::class, ['record' => $member->id])
        ->fillForm(['sort_order' => 5])
        ->call('save');

    expect($member->fresh()->is_published)->toBeTrue();
});

it('unpublishes a gallery image when a branch-head edits its alt text', function () {
    $image = GalleryImage::factory()->create(['branch_id' => $this->branch->id, 'is_published' => true, 'alt_text' => 'Old alt']);
    $this->actingAs($this->head);

    Livewire::test(EditGalleryImage::class, ['record' => $image->id])
        ->fillForm(['alt_text' => 'New alt'])
        ->call('save');

    expect($image->fresh()->is_published)->toBeFalse();
});

it('shows the pending approval filter only to a super-admin', function () {
    $pending = TeamMember::factory()->create(['is_published' => false, 'name' => 'Pending X']);
    $live = TeamMember::factory()->create(['is_published' => true, 'name' => 'Live X']);

    $this->actingAs($this->admin);
    Livewire::test(ListTeamMembers::class)
        ->filterTable('pending_approval')
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords([$live]);

    $this->actingAs($this->head);
    Livewire::test(ListTeamMembers::class)->assertTableFilterHidden('pending_approval');
});
