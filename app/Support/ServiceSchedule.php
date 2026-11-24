<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Presentation data for the homepage services section, built from upcoming occurrences and the branch's services.
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
    public static function build(Collection $occurrences, CarbonImmutable $now, ?Branch $branch = null): array
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
            'weekly' => self::weekly($branch, $today),
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
     * Built from the branch's active services, so paused services still appear.
     * Services with no remaining service date on or after today are left out.
     *
     * Optional keys: 'note' (one note for the whole line) or, when services on a
     * line differ, 'notes' (list of ['time' => label, 'note' => text]).
     *
     * @return Collection<int, array{day: string, name: string, times: array<int, string>, calendar_urls: array<int, string>, text: string, note?: string, notes?: array<int, array{time: string, note: string}>}>
     */
    private static function weekly(?Branch $branch, CarbonImmutable $today): Collection
    {
        if ($branch === null) {
            return collect();
        }

        // Service dates are in the app timezone (Manila); convert once so the filter and the notes agree on "today".
        $today = $today->setTimezone(config('app.timezone'))->startOfDay();

        return $branch->services()->active()->ordered()->get()
            // Same rule as the calendar link: keep a service only while it still has a date on or after today
            // (so its "add to calendar" link never 404s).
            ->filter(fn (Service $s) => ServiceIcs::available($s, $today))
            ->sortBy(fn (Service $s) => $s->start_time)
            ->groupBy(fn (Service $s) => $s->day_of_week.'|'.$s->name)
            ->map(function (Collection $items) use ($today) {
                $service = $items->first();
                $day = self::WEEKDAYS[$service->day_of_week];
                $times = $items->map(fn (Service $s) => CarbonImmutable::parse($s->start_time)->format('g:i A'))->values()->all();
                $notes = $items->map(fn (Service $s) => self::note($s, $today))->values()->all();

                $line = [
                    'sort' => $service->day_of_week,
                    'day' => $day,
                    'name' => $service->name,
                    'times' => $times,
                    'calendar_urls' => $items->map(fn (Service $s) => route('services.calendar', $s))->values()->all(),
                    'text' => $day.': '.$service->name.', '.implode(', ', $times),
                ];

                if (count(array_unique($notes, SORT_REGULAR)) === 1) {
                    if ($notes[0] !== null) {
                        $line['note'] = $notes[0];
                    }
                } else {
                    $line['notes'] = collect($times)
                        ->map(fn (string $time, int $i) => ['time' => $time, 'note' => $notes[$i]])
                        ->filter(fn (array $n) => $n['note'] !== null)
                        ->values()
                        ->all();
                }

                return $line;
            })
            ->sortBy('sort')
            ->values()
            ->map(fn (array $l) => collect($l)->except('sort')->all());
    }

    private static function note(Service $service, CarbonImmutable $today): ?string
    {
        if ($service->starts_on !== null && $service->starts_on->format('Y-m-d') > $today->format('Y-m-d')) {
            return 'Resumes '.$service->starts_on->format('M j, Y');
        }

        if ($service->ends_on !== null) {
            // The last service is the final matching weekday on or before ends_on.
            $endsOn = CarbonImmutable::parse($service->ends_on->format('Y-m-d'));
            $last = $endsOn->subDays(($endsOn->dayOfWeek - (int) $service->day_of_week + 7) % 7);

            if ($last->format('Y-m-d') >= $today->format('Y-m-d')
                && $last->format('Y-m-d') <= $today->addWeeks(8)->format('Y-m-d')) {
                return 'Last service '.$last->format('M j');
            }
        }

        return null;
    }
}
