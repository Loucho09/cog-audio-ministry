<?php

namespace App\Models;

use App\Enums\AvailabilityPart;
use App\Models\Concerns\BelongsToBranch;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseFactory(EventFactory::class)]
class Event extends Model
{
    use BelongsToBranch, HasFactory;

    protected $fillable = ['branch_id', 'service_id', 'name', 'starts_at', 'ends_at', 'notes'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function dutySlots(): HasMany
    {
        return $this->hasMany(EventDutySlot::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DutyAssignment::class);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('ends_at', '>=', now());
    }

    /** AM or PM block of this event, from its start time vs the configured PM cutoff. */
    public function block(): AvailabilityPart
    {
        $start = $this->starts_at->copy()->timezone(config('app.timezone'));
        $cutoff = $start->copy()->setTimeFromTimeString(config('ministry.duty_pm_starts_at', '15:00'));

        return $start->lt($cutoff) ? AvailabilityPart::Am : AvailabilityPart::Pm;
    }

    public function hasStarted(): bool
    {
        return $this->starts_at->lte(now());
    }
}
