<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * Removes an uploaded file from the public disk when its row is deleted or the
 * file is replaced. The model sets $uploadColumn. Bundled paths ("images/...")
 * are part of the app, not uploads, so they are never touched.
 */
trait DeletesOldUploads
{
    public static function bootDeletesOldUploads(): void
    {
        static::updated(function (self $model) {
            $column = $model->uploadColumn;

            if ($model->wasChanged($column)) {
                self::deleteUpload($model->getOriginal($column));
            }
        });

        static::deleted(function (self $model) {
            self::deleteUpload($model->getAttribute($model->uploadColumn));
        });
    }

    private static function deleteUpload(mixed $path): void
    {
        if (is_string($path) && $path !== '' && ! str_starts_with($path, 'images/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
