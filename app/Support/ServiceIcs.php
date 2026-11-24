<?php

namespace App\Support;

use App\Models\Service;
use Carbon\CarbonImmutable;

/**
 * Builds an RFC 5545 calendar file (one weekly recurring event) for a Service.
 */
class ServiceIcs
{
    /** Services have no stored length, so every calendar event is 90 minutes. */
    private const DURATION_MINUTES = 90;

    private const BYDAY = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];

    private const DAYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    /** The app timezone (Asia/Manila). The VTIMEZONE offsets below are still Manila's +08:00. */
    private static function tz(): string
    {
        return config('app.timezone');
    }

    /** Whether the service can still be added (active and not ended). */
    public static function available(Service $service, ?CarbonImmutable $now = null): bool
    {
        $today = ($now ?? CarbonImmutable::now(self::tz()))->setTimezone(self::tz())->format('Y-m-d');

        return $service->is_active
            && ($service->ends_on === null || $service->ends_on->format('Y-m-d') >= $today)
            && self::firstStart($service, $now) !== null;
    }

    public static function filename(Service $service): string
    {
        $slug = str($service->name)->slug()->toString() ?: 'service';

        return $slug.'-'.self::DAYS[$service->day_of_week].'.ics';
    }

    public static function build(Service $service, ?CarbonImmutable $now = null): string
    {
        $now = $now ?? CarbonImmutable::now(self::tz());
        $start = self::firstStart($service, $now) ?? $now;
        $end = $start->addMinutes(self::DURATION_MINUTES);
        $branch = $service->branch;

        $rrule = 'FREQ=WEEKLY;BYDAY='.self::BYDAY[$service->day_of_week];
        if ($service->ends_on !== null) {
            $until = CarbonImmutable::parse($service->ends_on->format('Y-m-d').' 23:59:59', self::tz())->utc();
            $rrule .= ';UNTIL='.$until->format('Ymd\THis\Z');
        }

        $location = filled($branch?->address) ? $branch->address : $branch?->name;
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//COG Audio Ministry//Services//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VTIMEZONE',
            'TZID:'.self::tz(),
            'BEGIN:STANDARD',
            'DTSTART:19700101T000000',
            'TZOFFSETFROM:+0800',
            'TZOFFSETTO:+0800',
            'TZNAME:PHT',
            'END:STANDARD',
            'END:VTIMEZONE',
            'BEGIN:VEVENT',
            'UID:service-'.$service->id.'@'.$host,
            'DTSTAMP:'.$now->utc()->format('Ymd\THis\Z'),
            'DTSTART;TZID='.self::tz().':'.$start->format('Ymd\THis'),
            'DTEND;TZID='.self::tz().':'.$end->format('Ymd\THis'),
            'RRULE:'.$rrule,
            'SUMMARY:'.self::escape($service->name),
        ];

        if (filled($location)) {
            $lines[] = 'LOCATION:'.self::escape($location);
        }

        $lines[] = 'DESCRIPTION:'.self::escape($service->name.($branch ? ' at '.$branch->name : ''));
        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map(self::fold(...), $lines))."\r\n";
    }

    /** First occurrence on/after max(today, starts_on), in Manila time; null if past ends_on. */
    private static function firstStart(Service $service, ?CarbonImmutable $now = null): ?CarbonImmutable
    {
        $now = ($now ?? CarbonImmutable::now(self::tz()))->setTimezone(self::tz());
        [$hour, $minute] = $service->startTimeParts();

        $from = $now->startOfDay();
        if ($service->starts_on !== null) {
            $startsOn = CarbonImmutable::parse($service->starts_on->format('Y-m-d'), self::tz())->startOfDay();
            if ($startsOn->greaterThan($from)) {
                $from = $startsOn;
            }
        }

        $first = $from->addDays(((int) $service->day_of_week - $from->dayOfWeek + 7) % 7)->setTime($hour, $minute);

        if ($service->ends_on !== null && $first->format('Y-m-d') > $service->ends_on->format('Y-m-d')) {
            return null;
        }

        return $first;
    }

    private static function escape(string $text): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\n", "\r"],
            ['\\\\', '\\;', '\\,', '\\n', '\\n', '\\n'],
            $text,
        );
    }

    /** Fold a content line to at most 75 octets, never splitting a UTF-8 character. */
    private static function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $out = [];
        $current = '';
        $limit = 75;

        foreach (mb_str_split($line) as $char) {
            if (strlen($current) + strlen($char) > $limit) {
                $out[] = $current;
                $current = '';
                $limit = 74; // continuation lines start with one space
            }
            $current .= $char;
        }
        $out[] = $current;

        return implode("\r\n ", $out);
    }
}
