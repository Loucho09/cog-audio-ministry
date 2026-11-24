<?php

namespace App\Support;

use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Presentation data for the homepage services section, built from upcoming occurrences.
 */
class ServiceSchedule
{
    private const WEEKDAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    /** Days before this many days from today are "soon"; later days are tucked away. */
    private const SOON_DAYS = 7;

    /**
     * @param  Collection<int, array{service: Service, starts_at: CarbonImmutable}>  $occurrences
     * @return array{soon: Collection, later: Collection, weekly: Collection}
     */
    public static function build(Collection $occurrences, CarbonImmutable $now): array
    {
        $today = $now->startOfDay();
        $cutoff = $today->addDays(self::SOON_DAYS);

        $days = $occurrences
            ->sortBy(fn (array $o) => $o['starts_at']->getTimestamp())
            ->groupBy(fn (array $o) => $o['starts_at']->format('Y-m-d'))
            ->map(fn (Collection $items) => [
                'date' => $items->first()['starts_at']->startOfDay(),
                'items' => $items->values(),
                'badge' => self::badge($items->first()['starts_at'], $today),
            ])
            ->values();

        return [
            'soon' => $days->filter(fn (array $d) => $d['date']->lessThan($cutoff))->values(),
            'later' => $days->filter(fn (array $d) => $d['date']->greaterThanOrEqualTo($cutoff))->values(),
            'weekly' => self::weekly($occurrences),
        ];
    }

    public static function badge(CarbonImmutable $date, CarbonImmutable $today): ?string
    {
        if ($date->isSameDay($today)) {
            return 'Today';
        }

        return $date->isSameDay($today->addDay()) ? 'Tomorrow' : null;
    }

    /**
     * One line per weekday + service name, e.g. "Sunday: Sunday Service, 5:30 AM, 7:30 AM".
     *
     * @return Collection<int, array{day: string, name: string, times: array<int, string>, text: string}>
     */
    private static function weekly(Collection $occurrences): Collection
    {
        return $occurrences
            ->unique(fn (array $o) => $o['service']->id)
            ->sortBy(fn (array $o) => $o['service']->start_time)
            ->groupBy(fn (array $o) => $o['service']->day_of_week.'|'.$o['service']->name)
            ->map(function (Collection $items) {
                $service = $items->first()['service'];
                $day = self::WEEKDAYS[$service->day_of_week];
                $times = $items->map(fn (array $o) => $o['starts_at']->format('g:i A'))->values()->all();

                return [
                    'sort' => $service->day_of_week,
                    'day' => $day,
                    'name' => $service->name,
                    'times' => $times,
                    'text' => $day.': '.$service->name.', '.implode(', ', $times),
                ];
            })
            ->sortBy('sort')
            ->values()
            ->map(fn (array $l) => collect($l)->except('sort')->all());
    }
}
