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

    /** True when the user marked the event's date (app timezone) as whole day or the event's AM/PM block. */
    public static function blocks(User $user, Event $event): bool
    {
        $date = $event->starts_at->copy()->setTimezone(config('app.timezone'))->toDateString();

        return static::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->whereIn('part', [AvailabilityPart::WholeDay, $event->block()])
            ->exists();
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('date', '>=', now()->toDateString());
    }
}
