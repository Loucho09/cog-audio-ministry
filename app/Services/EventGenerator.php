<?php

namespace App\Services;

use App\Exceptions\RosterRuleViolation;
use App\Models\Branch;
use App\Models\Event;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class EventGenerator
{
    /** Create events from the branch's active service schedule. Returns how many were created. */
    public function generate(Branch $branch, CarbonImmutable $from, CarbonImmutable $to): int
    {
        $tz = config('app.timezone');
        $from = $from->setTimezone($tz)->startOfDay();
        $to = $to->setTimezone($tz)->startOfDay();

        if ($to->lessThan($from)) {
            throw new RosterRuleViolation('The end date must be on or after the start date.');
        }

        if ($to->greaterThan($from->addMonthsNoOverflow(3))) {
            throw new RosterRuleViolation('Please pick a range of 3 months or less.');
        }

        $created = 0;

        $services = Service::query()
            ->where('branch_id', $branch->id)
            ->active()
            ->with('dutySlots')
            ->get();

        foreach ($services as $service) {
            for ($day = $from; $day->lessThanOrEqualTo($to); $day = $day->addDay()) {
                if ($day->dayOfWeek !== (int) $service->day_of_week) {
                    continue;
                }

                $date = $day->format('Y-m-d');

                if (($service->starts_on && $date < $service->starts_on->format('Y-m-d'))
                    || ($service->ends_on && $date > $service->ends_on->format('Y-m-d'))) {
                    continue;
                }

                $exists = Event::query()
                    ->where('service_id', $service->id)
                    ->whereDate('starts_at', $date)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $startsAt = CarbonImmutable::parse($date.' '.$service->start_time, $tz);

                // Never create an event that has already started (it could not be edited or deleted).
                if ($startsAt->lessThanOrEqualTo(now())) {
                    continue;
                }

                DB::transaction(function () use ($service, $branch, $startsAt) {
                    $event = Event::create([
                        'branch_id' => $branch->id,
                        'service_id' => $service->id,
                        'name' => $service->name,
                        'starts_at' => $startsAt,
                        'ends_at' => $startsAt->addHours(2),
                    ]);

                    foreach ($service->dutySlots as $slot) {
                        $event->dutySlots()->create([
                            'duty_role_id' => $slot->duty_role_id,
                            'quantity' => $slot->quantity,
                        ]);
                    }
                });

                $created++;
            }
        }

        return $created;
    }
}
