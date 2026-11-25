<?php

use App\Filament\Resources\GalleryImages\Pages\EditGalleryImage;
use App\Models\Branch;
use App\Models\GalleryImage;
use App\Models\TeamMember;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => Storage::fake('public'));

it('deletes the gallery file when the row is deleted or the image is replaced', function () {
    Storage::disk('public')->put('gallery/old.jpg', 'x');
    Storage::disk('public')->put('gallery/new.jpg', 'y');
    $image = GalleryImage::factory()->create(['path' => 'gallery/old.jpg']);

    $image->update(['path' => 'gallery/new.jpg']);
    Storage::disk('public')->assertMissing('gallery/old.jpg');
    Storage::disk('public')->assertExists('gallery/new.jpg');

    $image->update(['alt_text' => 'Other text']);
    Storage::disk('public')->assertExists('gallery/new.jpg');

    $image->delete();
    Storage::disk('public')->assertMissing('gallery/new.jpg');
});

it('deletes the team photo when the row is deleted or the photo is replaced', function () {
    Storage::disk('public')->put('team/old.jpg', 'x');
    Storage::disk('public')->put('team/new.jpg', 'y');
    $member = TeamMember::factory()->create(['photo_path' => 'team/old.jpg']);

    $member->update(['photo_path' => 'team/new.jpg']);
    Storage::disk('public')->assertMissing('team/old.jpg');

    $member->delete();
    Storage::disk('public')->assertMissing('team/new.jpg');
});

it('never touches bundled placeholder images', function () {
    $image = GalleryImage::factory()->create(['path' => 'images/placeholders/gallery-1.svg']);

    $image->update(['path' => 'gallery/new.jpg']);
    $image->delete();

    expect(file_exists(public_path('images/placeholders/gallery-1.svg')))->toBeTrue();
});

it('does not delete a file that another row still uses', function () {
    Storage::disk('public')->put('gallery/shared.jpg', 'x');
    $a = GalleryImage::factory()->create(['path' => 'gallery/shared.jpg']);
    // Bypass the save guard to simulate legacy data where two rows share a file.
    $b = GalleryImage::factory()->create();
    GalleryImage::query()->whereKey($b->id)->update(['path' => 'gallery/shared.jpg']);

    $a->delete();
    Storage::disk('public')->assertExists('gallery/shared.jpg');

    $b->refresh()->delete();
    Storage::disk('public')->assertMissing('gallery/shared.jpg');
});

it('does not delete a path outside the upload directory', function () {
    Storage::disk('public')->put('other/secret.jpg', 'x');
    Storage::disk('public')->put('team/photo.jpg', 'x');
    $image = GalleryImage::factory()->create();
    GalleryImage::query()->whereKey($image->id)->update(['path' => 'other/secret.jpg']);
    $image->refresh()->delete();

    $member = TeamMember::factory()->create();
    TeamMember::query()->whereKey($member->id)->update(['photo_path' => 'team/../other/secret.jpg']);
    $member->refresh()->delete();

    Storage::disk('public')->assertExists('other/secret.jpg');
    Storage::disk('public')->assertExists('team/photo.jpg');
});

it('rejects saving a path that another record uses or that is outside the directory', function () {
    Storage::disk('public')->put('gallery/theirs.jpg', 'x');
    GalleryImage::factory()->create(['path' => 'gallery/theirs.jpg']);
    $mine = GalleryImage::factory()->create();

    foreach (['gallery/theirs.jpg', 'team/x.jpg', 'gallery/../x.jpg', '/etc/passwd'] as $bad) {
        expect(fn () => $mine->update(['path' => $bad]))->toThrow(ValidationException::class);
    }

    expect($mine->fresh()->path)->toBe('images/placeholders/gallery-1.svg');
});

it('does not let a crafted Filament payload point a record at another record file', function () {
    Filament::setCurrentPanel('admin');
    $branch = Branch::factory()->create();
    $head = User::factory()->branchAdmin($branch)->create();
    Storage::disk('public')->put('gallery/theirs.jpg', 'x');
    GalleryImage::factory()->create(['path' => 'gallery/theirs.jpg']);
    $mine = GalleryImage::factory()->create(['branch_id' => $branch->id]);

    $this->actingAs($head);
    try {
        Livewire::test(EditGalleryImage::class, ['record' => $mine->id])
            ->set('data.path', ['gallery/theirs.jpg'])
            ->call('save');
    } catch (Throwable) {
        // A rejected or failed save is fine; the checks below are what matter.
    }

    expect($mine->fresh()->path)->toBe('images/placeholders/gallery-1.svg');
    Storage::disk('public')->assertExists('gallery/theirs.jpg');
});
