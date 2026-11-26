<?php

namespace App\Models;

use App\Enums\DutyStatus;
use App\Models\Concerns\BelongsToBranch;
use Database\Factories\DutyAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseFactory(DutyAssignmentFactory::class)]
class DutyAssignment extends Model
{
    use BelongsToBranch, HasFactory;

    protected $fillable = ['branch_id', 'event_id', 'duty_role_id', 'user_id', 'guest_name', 'assigned_by', 'status', 'responded_at'];

    protected function casts(): array
    {
        return [
            'status' => DutyStatus::class,
            'responded_at' => 'datetime',
            'reminded_day_at' => 'datetime',
            'reminded_soon_at' => 'datetime',
        ];
    }

    public function isGuest(): bool
    {
        return $this->user_id === null && filled($this->guest_name);
    }

    protected function displayName(): Attribute
    {
        return Attribute::get(fn () => $this->isGuest()
            ? $this->guest_name.' (Guest)'
            : $this->user?->name);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function dutyRole(): BelongsTo
    {
        return $this->belongsTo(DutyRole::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function swaps(): HasMany
    {
        return $this->hasMany(DutySwap::class);
    }

    public function liveSwap(): ?DutySwap
    {
        return $this->swaps()->live()->latest('id')->first();
    }

    /** Still serving: not declined, and not replaced by an approved swap. SwapRequested stays active. */
    public function scopeDeclined(Builder $query): Builder
    {
        return $query->where('status', DutyStatus::Declined->value);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [DutyStatus::Declined->value, DutyStatus::Swapped->value]);
    }
}
