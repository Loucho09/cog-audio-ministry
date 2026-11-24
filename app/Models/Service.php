<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(ServiceFactory::class)]
class Service extends Model
{
    use HasFactory;

    protected $fillable = ['branch_id', 'name', 'day_of_week', 'start_time', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Next start datetime (app timezone) at or after $from. If this week's
     * time has already passed, returns the same slot next week.
     */
    public function nextOccurrence(?CarbonInterface $from = null): CarbonImmutable
    {
        $from = $from
            ? CarbonImmutable::instance($from)->setTimezone(config('app.timezone'))
            : CarbonImmutable::now(config('app.timezone'));

        [$hour, $minute] = array_map('intval', explode(':', (string) $this->start_time));

        $candidate = $from
            ->startOfDay()
            ->addDays(((int) $this->day_of_week - $from->dayOfWeek + 7) % 7)
            ->setTime($hour, $minute);

        return $candidate->lessThan($from) ? $candidate->addWeek() : $candidate;
    }
}
