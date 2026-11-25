<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\DeletesOldUploads;
use App\Support\GalleryThumbnail;
use App\Support\ImageUrl;
use Database\Factories\GalleryImageFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(GalleryImageFactory::class)]
class GalleryImage extends Model
{
    use BelongsToBranch, DeletesOldUploads, HasFactory;

    protected string $uploadColumn = 'path';

    protected string $uploadDirectory = 'gallery';

    protected $fillable = ['branch_id', 'title', 'path', 'alt_text', 'sort_order', 'is_published'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_published' => 'boolean'];
    }

    /**
     * Keep the grid thumbnail in step with the photo: make it when a photo is added or replaced,
     * and remove the old one once no other row uses that photo (bundled "images/" paths can be shared).
     */
    protected static function booted(): void
    {
        static::saved(function (self $image) {
            if ($image->wasRecentlyCreated || $image->wasChanged('path')) {
                GalleryThumbnail::generate($image->path);
            }
        });

        static::updated(function (self $image) {
            if ($image->wasChanged('path')) {
                self::deleteThumbnailIfUnused($image, $image->getOriginal('path'));
            }
        });

        static::deleted(fn (self $image) => self::deleteThumbnailIfUnused($image, $image->path));
    }

    private static function deleteThumbnailIfUnused(self $image, mixed $path): void
    {
        if (! is_string($path) || $path === '') {
            return;
        }

        $stillUsed = static::query()->withoutGlobalScopes()->whereKeyNot($image->getKey())->where('path', $path)->exists();

        if (! $stillUsed) {
            GalleryThumbnail::delete($path);
        }
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn () => ImageUrl::for($this->path));
    }

    /** URL of the small grid copy, or null when there is none (small original, or not generated yet). */
    protected function thumbUrl(): Attribute
    {
        return Attribute::get(fn () => GalleryThumbnail::url($this->path));
    }

    public function isPlaceholder(): bool
    {
        return str_starts_with((string) $this->path, 'images/placeholders/');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('sort_order')->orderBy('id');
    }
}
