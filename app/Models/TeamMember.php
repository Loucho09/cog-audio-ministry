<?php

namespace App\Models;

use Database\Factories\TeamMemberFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(TeamMemberFactory::class)]
class TeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id', 'name', 'role_title', 'photo_path',
        'is_full_time', 'is_featured', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_full_time' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true)
            ->orderByDesc('is_full_time')
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}
