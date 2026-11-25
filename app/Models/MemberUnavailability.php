<?php

namespace App\Models;

use App\Enums\AvailabilityPart;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberUnavailability extends Model
{
    use BelongsToBranch;

    protected $fillable = ['user_id', 'branch_id', 'date', 'part', 'note'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'part' => AvailabilityPart::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('date', '>=', now()->toDateString());
    }
}
