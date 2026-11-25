<?php

use App\Models\GalleryImage;
use App\Support\GalleryThumbnail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->withoutVite();
});

function storeGalleryUpload(string $name, int $width, int $height): string
{
    return UploadedFile::fake()->image($name, $width, $height)->storeAs('gallery', $name, 'public');
}

it('creates an 800px webp thumbnail when a large photo is added', function () {
    $image = GalleryImage::factory()->create(['path' => storeGalleryUpload('big.jpg', 2000, 1500)]);

    $thumb = GalleryThumbnail::pathFor($image->path);
    Storage::disk('public')->assertExists($thumb);

    [$width] = getimagesizefromstring(Storage::disk('public')->get($thumb));
    expect($width)->toBe(GalleryThumbnail::WIDTH)
        ->and($image->thumb_url)->toBe(Storage::disk('public')->url($thumb));
});

it('skips the thumbnail for photos that are already small', function () {
    $image = GalleryImage::factory()->create(['path' => storeGalleryUpload('small.jpg', 600, 400)]);

    Storage::disk('public')->assertMissing(GalleryThumbnail::pathFor($image->path));
    expect($image->thumb_url)->toBeNull();
});

it('replaces the thumbnail when the photo changes and removes it when the photo is deleted', function () {
    $image = GalleryImage::factory()->create(['path' => storeGalleryUpload('first.jpg', 1600, 1200)]);
    $oldThumb = GalleryThumbnail::pathFor($image->path);

    $image->update(['path' => storeGalleryUpload('second.jpg', 1600, 1200)]);
    $newThumb = GalleryThumbnail::pathFor($image->path);

    Storage::disk('public')->assertMissing($oldThumb);
    Storage::disk('public')->assertExists($newThumb);

    $image->delete();

    Storage::disk('public')->assertMissing($newThumb);
});

it('shows the thumbnail with a srcset in the homepage grid, lazy loaded', function () {
    $image = GalleryImage::factory()->create(['path' => storeGalleryUpload('grid.jpg', 2000, 1500), 'alt_text' => 'Team at the console']);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('srcset="'.$image->thumb_url.' 800w, '.$image->url.' 1600w"')
        ->and($html)->not->toContain('loading="eager"');
});

it('backfills missing thumbnails with the artisan command', function () {
    $image = GalleryImage::factory()->create(['path' => storeGalleryUpload('old.jpg', 1800, 1200)]);
    Storage::disk('public')->delete(GalleryThumbnail::pathFor($image->path));

    $this->artisan('gallery:thumbnails')->expectsOutput('Gallery thumbnails created: 1.')->assertSuccessful();

    Storage::disk('public')->assertExists(GalleryThumbnail::pathFor($image->path));
});
