<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Database\Factories\DutyRoleFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseFactory(DutyRoleFactory::class)]
class DutyRole extends Model
{
    use BelongsToBranch, HasFactory;

    protected $fillable = ['branch_id', 'name', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function serviceSlots(): HasMany
    {
        return $this->hasMany(ServiceDutySlot::class);
    }

    public function eventSlots(): HasMany
    {
        return $this->hasMany(EventDutySlot::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DutyAssignment::class);
    }

    /** True when any service slot, event slot or assignment still points at this role. */
    public function isInUse(): bool
    {
        return $this->serviceSlots()->exists()
            || $this->eventSlots()->exists()
            || $this->assignments()->exists();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
