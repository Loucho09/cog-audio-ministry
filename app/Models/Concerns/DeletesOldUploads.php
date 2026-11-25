<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Keeps uploaded files safe.
 *
 * The model sets $uploadColumn (the path column) and $uploadDirectory (where its
 * uploads live, e.g. "gallery").
 *
 * - Saving: a new path must sit inside the upload directory (no "..", no leading
 *   slash) and must not already belong to another row. This stops a crafted form
 *   payload from pointing a record at someone else's file. Bundled paths
 *   ("images/...") are part of the app and may be shared.
 * - Deleting or replacing: the old file is removed only when it is inside the
 *   upload directory and no other row still uses it. Failures are reported, never thrown.
 */
trait DeletesOldUploads
{
    public static function bootDeletesOldUploads(): void
    {
        static::saving(function (self $model) {
            $column = $model->uploadColumn;
            $path = $model->getAttribute($column);

            if (! $model->isDirty($column) || ! is_string($path) || $path === '' || str_starts_with($path, 'images/')) {
                return;
            }

            if (! self::isOwnUploadPath($model, $path) || self::usedByAnotherRow($model, $path)) {
                throw ValidationException::withMessages(['data.'.$column => 'That file cannot be used. Please upload it again.']);
            }
        });

        static::updated(function (self $model) {
            $column = $model->uploadColumn;

            if ($model->wasChanged($column)) {
                self::deleteUpload($model, $model->getOriginal($column));
            }
        });

        static::deleted(function (self $model) {
            self::deleteUpload($model, $model->getAttribute($model->uploadColumn));
        });
    }

    private static function isOwnUploadPath(self $model, string $path): bool
    {
        return str_starts_with($path, trim($model->uploadDirectory, '/').'/')
            && ! str_contains($path, '..')
            && ! str_starts_with($path, '/')
            && ! str_contains($path, '\\');
    }

    private static function usedByAnotherRow(self $model, string $path): bool
    {
        return static::query()
            ->withoutGlobalScopes()
            ->when($model->exists, fn ($query) => $query->whereKeyNot($model->getKey()))
            ->where($model->uploadColumn, $path)
            ->exists();
    }

    private static function deleteUpload(self $model, mixed $path): void
    {
        if (! is_string($path) || $path === '' || ! self::isOwnUploadPath($model, $path)) {
            return;
        }

        try {
            if (! self::usedByAnotherRow($model, $path)) {
                Storage::disk('public')->delete($path);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
