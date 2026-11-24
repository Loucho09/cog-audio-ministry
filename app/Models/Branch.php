<?php

namespace App\Models;

use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseFactory(BranchFactory::class)]
class Branch extends Model
{
    use HasFactory;

    // is_main is intentionally NOT fillable: only seeders or super-admin code may set it explicitly.
    protected $fillable = ['name', 'slug', 'address', 'contact_number', 'email', 'facebook_url'];

    protected function casts(): array
    {
        return ['is_main' => 'boolean'];
    }

    /** Facebook link for public pages: https only, otherwise null (never render javascript: or http: links). */
    public function safeFacebookUrl(): ?string
    {
        $url = trim((string) $this->facebook_url);

        return str_starts_with(strtolower($url), 'https://') ? $url : null;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function galleryImages(): HasMany
    {
        return $this->hasMany(GalleryImage::class);
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function activeServices(): HasMany
    {
        return $this->services()->active()->ordered();
    }

    public function scopeMain(Builder $query): Builder
    {
        return $query->where('is_main', true);
    }

    public static function mainBranch(): ?self
    {
        return static::main()->first();
    }
}
