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
    protected $fillable = ['name', 'slug', 'address', 'contact_number', 'email'];

    protected function casts(): array
    {
        return ['is_main' => 'boolean'];
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
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
