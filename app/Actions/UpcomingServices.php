<?php

namespace App\Actions;

use App\Models\Branch;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class UpcomingServices
{
    /**
     * Occurrences of a branch's active services within the next $weeks weeks,
     * sorted by start datetime.
     *
     * @return Collection<int, array{service: Service, starts_at: CarbonImmutable}>
     */
    public static function forBranch(Branch $branch, int $weeks = 4, ?CarbonInterface $from = null): Collection
    {
        $from = $from
            ? CarbonImmutable::instance($from)->setTimezone(config('app.timezone'))
            : CarbonImmutable::now(config('app.timezone'));
        $until = $from->addWeeks($weeks);

        $items = collect();

        foreach ($branch->services()->active()->get() as $service) {
            $startsAt = $service->nextOccurrence($from);

            while ($startsAt->lessThan($until)) {
                $items->push(['service' => $service, 'starts_at' => $startsAt]);
                $startsAt = $startsAt->addWeek();
            }
        }

        return $items->sortBy(fn (array $i) => $i['starts_at']->getTimestamp())->values();
    }
}
