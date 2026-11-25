<?php

namespace App\Models;

use App\Enums\SwapStatus;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DutySwap extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'branch_id', 'duty_assignment_id', 'offered_by', 'claimed_by',
        'status', 'claimed_at', 'decided_by', 'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SwapStatus::class,
            'claimed_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(DutyAssignment::class, 'duty_assignment_id');
    }

    public function offeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'offered_by');
    }

    public function claimedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** Live open swaps in the user's branch that they did not offer. Overlap/availability are checked on claim. */
    public function scopeClaimableBy(Builder $query, User $user): Builder
    {
        return $query->live()
            ->where('status', SwapStatus::Open->value)
            ->where('branch_id', $user->branch_id)
            ->where('offered_by', '!=', $user->id);
    }

    /** Open or claimed swaps whose event has not started yet. */
    public function scopeLive(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [SwapStatus::Open->value, SwapStatus::Claimed->value])
            ->whereHas('assignment.event', fn ($q) => $q->where('starts_at', '>', now()));
    }
}
