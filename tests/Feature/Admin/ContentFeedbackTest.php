<?php

use App\Filament\Resources\GalleryImages\Pages\CreateGalleryImage;
use App\Filament\Resources\GalleryImages\Pages\EditGalleryImage;
use App\Filament\Resources\GalleryImages\Pages\ListGalleryImages;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Filament\Resources\TeamMembers\Pages\CreateTeamMember;
use App\Filament\Resources\TeamMembers\Pages\EditTeamMember;
use App\Filament\Resources\TeamMembers\Pages\ListTeamMembers;
use App\Filament\Support\ApprovalNotice;
use App\Models\Branch;
use App\Models\Event;
use App\Models\GalleryImage;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Support\Exceptions\Halt;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    Storage::fake('public');
    $this->branchA = Branch::factory()->create();
    $this->branchB = Branch::factory()->create();
});

it('tells a branch-head the new team member waits for approval', function () {
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    Livewire::test(CreateTeamMember::class)
        ->assertSee(ApprovalNotice::NOTE)
        ->fillForm(['name' => 'Waiting'])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified(ApprovalNotice::SAVED_TITLE);
});

it('tells a branch-head a changed team member waits for approval', function () {
    $member = TeamMember::factory()->create(['branch_id' => $this->branchA->id]);
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    Livewire::test(EditTeamMember::class, ['record' => $member->id])
        ->assertSee(ApprovalNotice::NOTE)
        ->fillForm(['name' => 'Changed'])
        ->call('save')
        ->assertNotified(ApprovalNotice::SAVED_TITLE);
});

it('tells a branch-head a new or changed gallery image waits for approval', function () {
    $image = GalleryImage::factory()->create(['branch_id' => $this->branchA->id]);
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    Livewire::test(CreateGalleryImage::class)
        ->assertSee(ApprovalNotice::NOTE)
        ->fillForm(['path' => UploadedFile::fake()->image('a.jpg'), 'alt_text' => 'A photo'])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified(ApprovalNotice::SAVED_TITLE);

    Livewire::test(EditGalleryImage::class, ['record' => $image->id])
        ->fillForm(['alt_text' => 'Changed alt'])
        ->call('save')
        ->assertNotified(ApprovalNotice::SAVED_TITLE);
});

it('keeps the default notification and hides the note for a super-admin', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(CreateTeamMember::class)
        ->assertDontSee(ApprovalNotice::NOTE)
        ->fillForm(['name' => 'Direct'])
        ->call('create')
        ->assertNotified('Created');
});

it('shows global rows as all churches in the branch column', function () {
    TeamMember::factory()->create(['branch_id' => null]);
    GalleryImage::factory()->create(['branch_id' => null]);

    $this->actingAs(User::factory()->branchHead($this->branchA)->create());
    Livewire::test(ListTeamMembers::class)->assertSee('All churches');
    Livewire::test(ListGalleryImages::class)->assertSee('All churches');

    $this->actingAs(User::factory()->superAdmin()->create());
    Livewire::test(ListTeamMembers::class)->assertSee('All churches');
    Livewire::test(ListGalleryImages::class)->assertSee('All churches');
});

it('defaults service sort order to 0 in the form and never saves NULL', function () {
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    Livewire::test(CreateService::class)
        ->assertFormSet(['sort_order' => 0])
        ->fillForm(['name' => 'Sorted', 'day_of_week' => 3, 'start_time' => '19:00', 'sort_order' => null])
        ->call('create');

    expect(Service::where('name', 'Sorted')->firstOrFail()->sort_order)->toBe(0);
    expect((new Service)->sort_order)->toBe(0);
});

it('shows a friendly error instead of a 500 when the database rejects a duplicate service slot', function () {
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());
    Service::factory()->create(['branch_id' => $this->branchA->id, 'day_of_week' => 3, 'start_time' => '19:00:00']);

    // Call the save step directly so the form rule is skipped and the DB constraint fires (race case).
    $test = Livewire::test(CreateService::class);
    $page = $test->instance();
    $method = new ReflectionMethod($page, 'handleRecordCreation');

    expect(fn () => $method->invoke($page, [
        'branch_id' => $this->branchA->id,
        'name' => 'Dup',
        'day_of_week' => 3,
        'start_time' => '19:00:00',
    ]))->toThrow(Halt::class);

    expect(Service::where('name', 'Dup')->exists())->toBeFalse();
    $test->assertNotified('A service already runs at this day and time.');
});

it('keeps events when a service is deleted', function () {
    $service = Service::factory()->create(['branch_id' => $this->branchA->id]);
    $event = Event::factory()->create(['branch_id' => $this->branchA->id, 'service_id' => $service->id]);
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    Livewire::test(EditService::class, ['record' => $service->id])
        ->callAction('delete');

    expect(Service::find($service->id))->toBeNull();
    expect($event->fresh())->not->toBeNull()->and($event->fresh()->service_id)->toBeNull();
});

it('cannot bulk delete another branch services', function () {
    $mine = Service::factory()->create(['branch_id' => $this->branchA->id]);
    $theirs = Service::factory()->create(['branch_id' => $this->branchB->id]);
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    Livewire::test(ListServices::class)
        ->callTableBulkAction('delete', [$mine, $theirs]);

    expect(Service::find($mine->id))->toBeNull();
    expect(Service::find($theirs->id))->not->toBeNull();
});

it('cannot bulk delete another branch or global team members', function () {
    $mine = TeamMember::factory()->create(['branch_id' => $this->branchA->id]);
    $theirs = TeamMember::factory()->create(['branch_id' => $this->branchB->id]);
    $global = TeamMember::factory()->create(['branch_id' => null]);
    $this->actingAs(User::factory()->branchHead($this->branchA)->create());

    Livewire::test(ListTeamMembers::class)
        ->callTableBulkAction('delete', [$mine, $theirs, $global]);

    expect(TeamMember::find($mine->id))->toBeNull();
    expect(TeamMember::find($theirs->id))->not->toBeNull();
    expect(TeamMember::find($global->id))->not->toBeNull();
});
