<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Small WebP copies of gallery photos for the homepage grid.
 *
 * Uploads can be 2000px+ wide, but a grid tile is ~400px. Each photo wider than the smallest tier gets
 * one WebP per tier (capped at the photo's own width), stored on the public disk under thumbs/gallery/
 * as "<sha1 of source path>-<width>.webp", outside the gallery upload directory. The width in the name
 * is the srcset descriptor, so no extra column is needed. The original stays the lightbox source.
 *
 * Uses PHP's built-in GD extension (no extra package). Safety:
 * - Only bundled "images/..." files under public/images or files on the public disk are read (no "..").
 * - Pixel count is checked before decoding: GD needs ~5 bytes per pixel, and running out of memory is a
 *   fatal error that no catch can stop, so oversized photos simply get no thumbnail.
 * - JPEG EXIF orientation is applied (phone photos are often stored sideways); the exif extension is
 *   not required.
 */
class GalleryThumbnail
{
    /** Tile widths: 800 for phones and 1x screens, 1600 for big tiles on sharp screens. */
    public const TIERS = [800, 1600];

    /** ~24 MP (a 6000x4000 camera photo) keeps GD well inside a 256 MB memory limit. */
    public const MAX_PIXELS = 24_000_000;

    private const DIRECTORY = 'thumbs/gallery';

    public static function pathFor(string $source, int $width): string
    {
        return self::DIRECTORY.'/'.sha1($source).'-'.$width.'.webp';
    }

    /**
     * All thumbnails, grouped by source hash, from ONE directory listing (not one exists() call per photo).
     *
     * @return array<string, array<int, string>> hash => [width => url], widths ascending
     */
    public static function index(): array
    {
        $disk = Storage::disk('public');
        $index = [];

        foreach ($disk->files(self::DIRECTORY) as $file) {
            if (preg_match('/([0-9a-f]{40})-(\d+)\.webp$/', $file, $m) && $disk->size($file) > 0) {
                $index[$m[1]][(int) $m[2]] = $disk->url($file);
            }
        }

        foreach ($index as &$variants) {
            ksort($variants);
        }

        return $index;
    }

    /**
     * @param  array<string, array<int, string>>  $index  from index()
     * @return array<int, string> width => url (empty when the photo has no thumbnails)
     */
    public static function variants(?string $source, array $index): array
    {
        return blank($source) ? [] : ($index[sha1($source)] ?? []);
    }

    /**
     * Create (or replace) the thumbnails. Returns false when there is nothing to do (unsafe path,
     * missing file, not an image, too many pixels, already small) or when GD fails; never throws.
     */
    public static function generate(?string $source): bool
    {
        $file = self::resolve($source);

        if ($file === null || ! function_exists('imagewebp')) {
            return false;
        }

        $info = @getimagesize($file);

        if ($info === false || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > self::MAX_PIXELS) {
            return false;
        }

        $orientation = $info[2] === IMAGETYPE_JPEG ? self::jpegOrientation($file) : 1;
        $quarterTurn = in_array($orientation, [5, 6, 7, 8], true);
        // Width as the photo is shown (after rotation).
        $shownWidth = $quarterTurn ? $info[1] : $info[0];
        $shownHeight = $quarterTurn ? $info[0] : $info[1];

        if ($shownWidth <= self::TIERS[0]) {
            return false;
        }

        self::delete($source);

        $image = null;

        try {
            $image = @imagecreatefromstring((string) file_get_contents($file));

            if ($image === false) {
                return false;
            }

            $made = false;

            foreach (array_unique(array_map(fn (int $tier) => min($tier, $shownWidth), self::TIERS)) as $width) {
                $made = self::writeTier($image, $source, $width, (int) round($shownHeight * $width / $shownWidth), $orientation) || $made;
            }

            return $made;
        } catch (Throwable $e) {
            report($e);

            return false;
        } finally {
            if ($image instanceof \GdImage) {
                imagedestroy($image);
            }
        }
    }

    public static function delete(?string $source): void
    {
        if (blank($source)) {
            return;
        }

        try {
            $disk = Storage::disk('public');
            $prefix = self::DIRECTORY.'/'.sha1($source).'-';
            $disk->delete(array_values(array_filter($disk->files(self::DIRECTORY), fn (string $file) => str_starts_with($file, $prefix))));
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Absolute file path for a safe source path, or null. */
    private static function resolve(?string $source): ?string
    {
        if (blank($source) || str_contains($source, '..') || str_contains($source, '\\') || str_starts_with($source, '/')) {
            return null;
        }

        $file = str_starts_with($source, 'images/')
            ? public_path($source)
            : Storage::disk('public')->path($source);

        return is_file($file) ? $file : null;
    }

    /** Scale (and turn upright), then encode one tier. An empty or failed encode writes nothing. */
    private static function writeTier(\GdImage $image, string $source, int $width, int $height, int $orientation): bool
    {
        $quarterTurn = in_array($orientation, [5, 6, 7, 8], true);
        // Scale the stored (unrotated) bitmap first, so rotation works on the small copy.
        $scaled = imagescale($image, $quarterTurn ? $height : $width, $quarterTurn ? $width : $height, IMG_BICUBIC);

        if ($scaled === false) {
            return false;
        }

        $upright = self::applyOrientation($scaled, $orientation);
        imagealphablending($upright, false);
        imagesavealpha($upright, true);

        ob_start();

        try {
            $ok = imagewebp($upright, null, 80);
        } finally {
            $bytes = (string) ob_get_clean();
            imagedestroy($upright);
        }

        return $ok && $bytes !== '' && Storage::disk('public')->put(self::pathFor($source, $width), $bytes);
    }

    private static function applyOrientation(\GdImage $image, int $orientation): \GdImage
    {
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        $angle = match ($orientation) {
            3, 4 => 180,
            5, 6 => 270, // 90 degrees clockwise
            7, 8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        if ($rotated === false) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }

    /** EXIF orientation (1-8) from a JPEG's APP1 segment, read without the exif extension. 1 when absent. */
    public static function jpegOrientation(string $file): int
    {
        $data = (string) @file_get_contents($file, false, null, 0, 131072);

        if (! str_starts_with($data, "\xFF\xD8")) {
            return 1;
        }

        $offset = 2;
        $length = strlen($data);

        while ($offset + 4 <= $length && $data[$offset] === "\xFF") {
            $marker = ord($data[$offset + 1]);
            $size = unpack('n', substr($data, $offset + 2, 2))[1];

            if ($marker === 0xE1 && substr($data, $offset + 4, 6) === "Exif\0\0") {
                return self::tiffOrientation(substr($data, $offset + 10, $size - 8));
            }

            if ($marker === 0xDA || $size < 2) {
                break; // Image data starts; no EXIF before it.
            }

            $offset += 2 + $size;
        }

        return 1;
    }

    private static function tiffOrientation(string $tiff): int
    {
        if (strlen($tiff) < 8) {
            return 1;
        }

        $little = substr($tiff, 0, 2) === 'II';
        $short = fn (int $at) => strlen($tiff) >= $at + 2 ? unpack($little ? 'v' : 'n', substr($tiff, $at, 2))[1] : 0;
        $long = fn (int $at) => strlen($tiff) >= $at + 4 ? unpack($little ? 'V' : 'N', substr($tiff, $at, 4))[1] : 0;

        $ifd = $long(4);
        $count = $short($ifd);

        for ($i = 0; $i < $count; $i++) {
            $entry = $ifd + 2 + $i * 12;

            if ($short($entry) === 0x0112) {
                $value = $short($entry + 8);

                return $value >= 1 && $value <= 8 ? $value : 1;
            }
        }

        return 1;
    }
}
