<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\DeletesOldUploads;
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

    protected function url(): Attribute
    {
        return Attribute::get(fn () => ImageUrl::for($this->path));
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
