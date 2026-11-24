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
use InvalidArgumentException;

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

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return array{0: int, 1: int} [hour, minute]
     *
     * @throws InvalidArgumentException when start_time is missing or malformed
     */
    public function startTimeParts(): array
    {
        $time = (string) $this->start_time;

        if (! preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $time, $m)) {
            throw new InvalidArgumentException('Service start_time is missing or invalid.');
        }

        return [(int) $m[1], (int) $m[2]];
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

        [$hour, $minute] = $this->startTimeParts();

        $candidate = $from
            ->startOfDay()
            ->addDays(((int) $this->day_of_week - $from->dayOfWeek + 7) % 7)
            ->setTime($hour, $minute);

        return $candidate->lessThan($from) ? $candidate->addWeek() : $candidate;
    }
}
