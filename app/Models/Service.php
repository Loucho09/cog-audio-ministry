<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

#[UseFactory(ServiceFactory::class)]
class Service extends Model
{
    use BelongsToBranch, HasFactory;

    /** New rows never get a NULL sort order. */
    protected $attributes = ['sort_order' => 0];

    protected $fillable = ['branch_id', 'name', 'day_of_week', 'start_time', 'is_active', 'starts_on', 'ends_on', 'sort_order'];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_active' => 'boolean',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'sort_order' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function dutySlots(): HasMany
    {
        return $this->hasMany(ServiceDutySlot::class);
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

    /** Whether the service runs on the given date (date only; null bounds are open). */
    public function runsOn(CarbonInterface $date): bool
    {
        $day = $date->format('Y-m-d');

        return ($this->starts_on === null || $day >= $this->starts_on->format('Y-m-d'))
            && ($this->ends_on === null || $day <= $this->ends_on->format('Y-m-d'));
    }

    /**
     * Next start datetime (app timezone) at or after $from, within the
     * service's date window. Returns null once the window has ended.
     */
    public function nextOccurrence(?CarbonInterface $from = null): ?CarbonImmutable
    {
        $tz = config('app.timezone');
        $from = $from
            ? CarbonImmutable::instance($from)->setTimezone($tz)
            : CarbonImmutable::now($tz);

        [$hour, $minute] = $this->startTimeParts();

        if ($this->starts_on !== null && $from->format('Y-m-d') < $this->starts_on->format('Y-m-d')) {
            $from = CarbonImmutable::parse($this->starts_on->format('Y-m-d'), $tz)->startOfDay();
        }

        $candidate = $from
            ->startOfDay()
            ->addDays(((int) $this->day_of_week - $from->dayOfWeek + 7) % 7)
            ->setTime($hour, $minute);

        if ($candidate->lessThan($from)) {
            $candidate = $candidate->addWeek();
        }

        return $this->runsOn($candidate) ? $candidate : null;
    }
}
