<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Database\Factories\JoinApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(JoinApplicationFactory::class)]
class JoinApplication extends Model
{
    use BelongsToBranch, HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const INTERESTS = ['FOH', 'Monitors', 'Livestream', 'Stage Tech', 'Other'];

    /** status, reviewed_by, reviewed_at and user_id are set by the review action only. */
    protected $fillable = [
        'branch_id', 'full_name', 'age', 'contact_number', 'email', 'facebook_url',
        'interests', 'experience', 'availability', 'consented_at',
    ];

    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'interests' => 'array',
            'consented_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Overrides the trait scope: only super-admin and branch staff may see
     * applications at all (plain members get nothing, even in their own branch).
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user === null || ! $user->hasAnyRole(['super-admin', 'branch-head', 'branch-admin'])) {
            return $query->whereKey([]);
        }

        if ($user->hasRole('super-admin')) {
            return $query;
        }

        return $user->branch_id === null
            ? $query->whereKey([])
            : $query->where($query->getModel()->getTable().'.branch_id', $user->branch_id);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
