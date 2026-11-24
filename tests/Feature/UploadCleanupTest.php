<?php

use App\Models\GalleryImage;
use App\Models\TeamMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

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
