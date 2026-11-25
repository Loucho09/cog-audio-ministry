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

function thumbWidth(string $path): int
{
    return getimagesizefromstring(Storage::disk('public')->get($path))[0];
}

/** A minimal JPEG whose APP1 EXIF block sets the given orientation (big-endian TIFF, one IFD0 entry). */
function jpegWithOrientation(int $width, int $height, int $orientation): string
{
    $image = imagecreatetruecolor($width, $height);
    ob_start();
    imagejpeg($image);
    $jpeg = ob_get_clean();

    $tiff = 'MM'.pack('n', 42).pack('N', 8).pack('n', 1)
        .pack('n', 0x0112).pack('n', 3).pack('N', 1).pack('n', $orientation).pack('n', 0)
        .pack('N', 0);
    $app1 = "\xFF\xE1".pack('n', strlen($tiff) + 8)."Exif\0\0".$tiff;

    return "\xFF\xD8".$app1.substr($jpeg, 2);
}

it('creates 800 and 1600 px webp thumbnails for a large photo', function () {
    $image = GalleryImage::factory()->create(['path' => storeGalleryUpload('big.jpg', 2000, 1500)]);

    expect(thumbWidth(GalleryThumbnail::pathFor($image->path, 800)))->toBe(800)
        ->and(thumbWidth(GalleryThumbnail::pathFor($image->path, 1600)))->toBe(1600)
        ->and(array_keys(GalleryThumbnail::variants($image->path, GalleryThumbnail::index())))->toBe([800, 1600]);
});

it('caps the big tier at the photo width', function () {
    $image = GalleryImage::factory()->create(['path' => storeGalleryUpload('mid.jpg', 1200, 900)]);

    expect(array_keys(GalleryThumbnail::variants($image->path, GalleryThumbnail::index())))->toBe([800, 1200]);
});

it('skips thumbnails for small photos and non-images', function () {
    $small = GalleryImage::factory()->create(['path' => storeGalleryUpload('small.jpg', 600, 400)]);
    Storage::disk('public')->put('gallery/broken.jpg', 'not an image');
    $broken = GalleryImage::factory()->create(['path' => 'gallery/broken.jpg']);

    expect(GalleryThumbnail::index())->toBe([])
        ->and(GalleryThumbnail::generate($small->path))->toBeFalse()
        ->and(GalleryThumbnail::generate($broken->path))->toBeFalse();
});

it('skips photos over the pixel limit without decoding them', function () {
    // A small PNG whose header claims 6000x4100 (24.6 MP): the size check must reject it from the header alone.
    $png = UploadedFile::fake()->image('seed.png', 10, 10)->getContent();
    $png = substr_replace($png, pack('N', 6000).pack('N', 4100), 16, 8);
    Storage::disk('public')->put($path = 'gallery/huge.png', $png);

    expect(GalleryThumbnail::generate($path))->toBeFalse()
        ->and(GalleryThumbnail::index())->toBe([]);
});

it('refuses unsafe source paths', function (string $path) {
    expect(GalleryThumbnail::generate($path))->toBeFalse();
})->with(['images/../storage/app/private/x.png', '/etc/passwd', 'gallery\\..\\x.jpg']);

it('turns sideways phone photos upright', function () {
    // Stored 2000x1000 with orientation 6 (rotate 90 degrees clockwise): shown as 1000 wide, 2000 tall.
    Storage::disk('public')->put('gallery/phone.jpg', jpegWithOrientation(2000, 1000, 6));

    expect(GalleryThumbnail::jpegOrientation(Storage::disk('public')->path('gallery/phone.jpg')))->toBe(6);

    $image = GalleryImage::factory()->create(['path' => 'gallery/phone.jpg']);
    [$width, $height] = getimagesizefromstring(Storage::disk('public')->get(GalleryThumbnail::pathFor($image->path, 800)));

    expect([$width, $height])->toBe([800, 1600])
        ->and(array_keys(GalleryThumbnail::variants($image->path, GalleryThumbnail::index())))->toBe([800, 1000]);
});

it('replaces thumbnails when the photo changes and removes them when the photo is deleted', function () {
    $image = GalleryImage::factory()->create(['path' => storeGalleryUpload('first.jpg', 1600, 1200)]);
    $first = $image->path;

    $image->update(['path' => storeGalleryUpload('second.jpg', 1600, 1200)]);

    expect(GalleryThumbnail::variants($first, GalleryThumbnail::index()))->toBe([])
        ->and(GalleryThumbnail::variants($image->path, GalleryThumbnail::index()))->not->toBe([]);

    $image->delete();

    expect(GalleryThumbnail::index())->toBe([]);
});

it('removes the old thumbnails when a large photo is swapped for a small one', function () {
    $image = GalleryImage::factory()->create(['path' => storeGalleryUpload('large.jpg', 1600, 1200)]);

    $image->update(['path' => storeGalleryUpload('tiny.jpg', 500, 400)]);

    expect(GalleryThumbnail::index())->toBe([]);
});

it('keeps thumbnails of a shared photo until no row uses it', function () {
    $path = storeGalleryUpload('shared.jpg', 1600, 1200);
    $a = GalleryImage::factory()->create(['path' => $path]);
    // Bypass the upload guard (which blocks shared uploads) to model a shared bundled path.
    $b = GalleryImage::withoutEvents(fn () => GalleryImage::factory()->create(['path' => $path]));

    $a->delete();
    expect(GalleryThumbnail::variants($path, GalleryThumbnail::index()))->not->toBe([]);

    $b->delete();
    expect(GalleryThumbnail::variants($path, GalleryThumbnail::index()))->toBe([]);
});

it('shows the thumbnails as a srcset in the homepage grid, lazy loaded, with the full photo only in the lightbox', function () {
    $image = GalleryImage::factory()->create(['path' => storeGalleryUpload('grid.jpg', 2000, 1500), 'alt_text' => 'Team at the console']);
    $thumbs = GalleryThumbnail::variants($image->path, GalleryThumbnail::index());

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('srcset="'.$thumbs[800].' 800w, '.$thumbs[1600].' 1600w"')
        ->and($html)->not->toContain('loading="eager"')
        ->and($html)->toContain('<template x-if="open">');
});

it('backfills missing thumbnails with the artisan command', function () {
    $image = GalleryImage::factory()->create(['path' => storeGalleryUpload('old.jpg', 1800, 1200)]);
    GalleryThumbnail::delete($image->path);

    $this->artisan('gallery:thumbnails')->expectsOutput('Gallery thumbnails created: 1.')->assertSuccessful();

    expect(GalleryThumbnail::variants($image->path, GalleryThumbnail::index()))->not->toBe([]);
});
