<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class ImageUrl
{
    /**
     * Paths starting with "images/" are bundled files under public/ (asset()).
     * Any other path is a file on the "public" storage disk (e.g. Filament uploads).
     */
    public static function for(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        return Storage::disk('public')->url($path);
    }
}
