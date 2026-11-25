<?php

use App\Filament\Resources\GalleryImages\Pages\CreateGalleryImage;
use App\Filament\Resources\GalleryImages\Pages\EditGalleryImage;
use App\Filament\Resources\GalleryImages\Pages\ListGalleryImages;
use App\Models\Branch;
use App\Models\GalleryImage;
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
    $this->imageA = GalleryImage::factory()->create(['branch_id' => $this->branchA->id, 'title' => 'Alpha']);
    $this->imageB = GalleryImage::factory()->create(['branch_id' => $this->branchB->id, 'title' => 'Bravo']);
    $this->imageGlobal = GalleryImage::factory()->create(['branch_id' => null, 'title' => 'Global']);
});

it('lets a super-admin see every record', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(ListGalleryImages::class)
        ->assertCanSeeTableRecords([$this->imageA, $this->imageB, $this->imageGlobal]);
});

it('lets a super-admin create a global image with an upload', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(CreateGalleryImage::class)
        ->fillForm([
            'branch_id' => null,
            'path' => UploadedFile::fake()->image('photo.jpg'),
            'alt_text' => 'Team at the sound desk',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $image = GalleryImage::where('alt_text', 'Team at the sound desk')->firstOrFail();
    expect($image->branch_id)->toBeNull();
    Storage::disk('public')->assertExists($image->path);
    expect($image->path)->toStartWith('gallery/');
});

it('shows a branch-admin own and global records but not other branches', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(ListGalleryImages::class)
        ->assertCanSeeTableRecords([$this->imageA, $this->imageGlobal])
        ->assertCanNotSeeTableRecords([$this->imageB]);
});

it('forces a branch-admin new image into their own branch', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(CreateGalleryImage::class)
        ->fillForm([
            'branch_id' => $this->branchB->id,
            'path' => UploadedFile::fake()->image('photo.png'),
            'alt_text' => 'Forced',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(GalleryImage::where('alt_text', 'Forced')->firstOrFail()->branch_id)->toBe($this->branchA->id);
});

it('returns 403 for a branch-admin editing a global record and 404 for another branch', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    $this->get("/admin/gallery-images/{$this->imageGlobal->id}/edit")->assertForbidden();
    $this->get("/admin/gallery-images/{$this->imageB->id}/edit")->assertNotFound();
});

it('keeps the bundled image path when editing without re-uploading', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(EditGalleryImage::class, ['record' => $this->imageA->id])
        ->fillForm(['alt_text' => 'Updated alt'])
        ->call('save')
        ->assertHasNoFormErrors();

    $fresh = $this->imageA->fresh();
    expect($fresh->alt_text)->toBe('Updated alt')
        ->and($fresh->path)->toBe('images/placeholders/gallery-1.svg');
});

it('requires alt text', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(CreateGalleryImage::class)
        ->fillForm(['path' => UploadedFile::fake()->image('photo.jpg'), 'alt_text' => ''])
        ->call('create')
        ->assertHasFormErrors(['alt_text' => 'required']);
});

it('rejects a non-image upload', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(CreateGalleryImage::class)
        ->fillForm([
            'path' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            'alt_text' => 'Not an image',
        ])
        ->call('create')
        ->assertHasFormErrors(['path']);

    expect(GalleryImage::where('alt_text', 'Not an image')->exists())->toBeFalse();
});

it('forbids members from the gallery page', function () {
    $this->actingAs(User::factory()->member($this->branchA)->create());

    $this->get('/admin/gallery-images')->assertForbidden();
});

it('does not let a branch-admin bulk delete global images', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(ListGalleryImages::class)
        ->callTableBulkAction('delete', [$this->imageA, $this->imageGlobal]);

    expect(GalleryImage::find($this->imageGlobal->id))->not->toBeNull()
        ->and(GalleryImage::find($this->imageA->id))->toBeNull();
});

it('shows tab counts scoped to the branch-admin', function () {
    $this->imageA->update(['is_published' => false]);
    $this->imageB->update(['is_published' => false]);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    $test = Livewire::test(ListGalleryImages::class)->assertSuccessful();
    $tabs = $test->instance()->getTabs();

    // own + global rows only; the other branch's pending image is not counted.
    expect($tabs['all']->getBadge())->toEqual(2)
        ->and($tabs['published']->getBadge())->toEqual(1)
        ->and($tabs['pending']->getBadge())->toEqual(1);

    $test->set('activeTab', 'pending')
        ->assertCanSeeTableRecords([$this->imageA])
        ->assertCanNotSeeTableRecords([$this->imageB, $this->imageGlobal]);
});

it('renders the edit page summary card', function () {
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    Livewire::test(EditGalleryImage::class, ['record' => $this->imageA->id])
        ->assertSuccessful()
        ->assertSee('Summary')
        ->assertSee('Alpha');
});

it('excludes other branches from a branch-admin tab counts', function () {
    GalleryImage::factory()->count(3)->create(['branch_id' => $this->branchB->id, 'is_published' => false]);
    $this->actingAs(User::factory()->branchAdmin($this->branchA)->create());

    $tabs = Livewire::test(ListGalleryImages::class)->instance()->getTabs();

    expect($tabs['all']->getBadge())->toEqual(2)
        ->and($tabs['published']->getBadge())->toEqual(2)
        ->and($tabs['pending']->getBadge())->toBeNull();
});
