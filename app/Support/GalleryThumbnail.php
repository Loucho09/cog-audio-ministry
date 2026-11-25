<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Small WebP copies of gallery photos for the homepage grid.
 *
 * Uploads can be 2000px+ wide, but a grid tile is ~400px. The thumbnail is WIDTH px wide
 * (sharp on 2x screens) and lives on the public disk under thumbs/gallery/, outside the
 * gallery upload directory, named by a hash of the source path. The original stays the
 * full-size source for the lightbox. Uses PHP's built-in GD extension (no extra package).
 */
class GalleryThumbnail
{
    public const WIDTH = 800;

    public static function pathFor(string $source): string
    {
        return 'thumbs/gallery/'.sha1($source).'.webp';
    }

    public static function url(?string $source): ?string
    {
        if (blank($source)) {
            return null;
        }

        $path = self::pathFor($source);

        return Storage::disk('public')->exists($path) ? Storage::disk('public')->url($path) : null;
    }

    /**
     * Create (or replace) the thumbnail. Returns false when there is nothing to do
     * (missing file, not an image, already small) or when GD fails; failures are reported, never thrown.
     */
    public static function generate(?string $source): bool
    {
        if (blank($source)) {
            return false;
        }

        $file = str_starts_with($source, 'images/')
            ? public_path($source)
            : Storage::disk('public')->path($source);

        if (! is_file($file) || ! function_exists('imagewebp')) {
            return false;
        }

        try {
            $image = @imagecreatefromstring((string) file_get_contents($file));

            if ($image === false) {
                return false;
            }

            if (imagesx($image) <= self::WIDTH) {
                imagedestroy($image);

                return false;
            }

            $scaled = imagescale($image, self::WIDTH, -1, IMG_BICUBIC);
            imagedestroy($image);

            if ($scaled === false) {
                return false;
            }

            ob_start();
            imagewebp($scaled, null, 80);
            $bytes = (string) ob_get_clean();
            imagedestroy($scaled);

            return Storage::disk('public')->put(self::pathFor($source), $bytes);
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    public static function delete(?string $source): void
    {
        if (blank($source)) {
            return;
        }

        try {
            Storage::disk('public')->delete(self::pathFor($source));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
