<?php

namespace Database\Seeders;

use App\Enums\DutyStatus;
use App\Exceptions\RosterRuleViolation;
use App\Models\Branch;
use App\Models\DutyRole;
use App\Models\Event;
use App\Models\Service;
use App\Models\User;
use App\Services\DutyRosterService;
use App\Services\EventGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Demo roster. Needs demo users, so DatabaseSeeder only runs it in local/testing.
 * Goes through the services so roster rules hold; rule violations are skipped.
 */
class DutyRosterSeeder extends Seeder
{
    public function run(EventGenerator $generator, DutyRosterService $roster): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Skipping demo duty roster outside local/testing.');

            return;
        }

        // TODO: real roles from owner
        // TODO: real counts from owner
        $roleCounts = ['Duty role A' => 1, 'Duty role B' => 1, 'Duty role C' => 2];

        $from = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $to = $from->addWeeks(4);
        $n = 0; // rotation counter across the whole seed

        foreach (Branch::orderBy('id')->get() as $branch) {
            $roles = [];
            $sort = 1;
            foreach ($roleCounts as $name => $qty) {
                $roles[$name] = DutyRole::firstOrCreate(
                    ['branch_id' => $branch->id, 'name' => $name],
                    ['sort_order' => $sort++, 'is_active' => true],
                );
            }

            $services = Service::where('branch_id', $branch->id)->get();
            foreach ($services as $service) {
                foreach ($roleCounts as $name => $qty) {
                    $service->dutySlots()->firstOrCreate(
                        ['duty_role_id' => $roles[$name]->id],
                        ['quantity' => $qty],
                    );
                }
            }

            if ($services->isEmpty()) {
                continue;
            }

            $generator->generate($branch, $from, $to);

            $members = User::where('branch_id', $branch->id)
                ->role('member')->orderBy('id')->get();
            if ($members->isEmpty()) {
                continue;
            }
            $head = User::where('branch_id', $branch->id)->role('branch-admin')->first();

            $events = Event::where('branch_id', $branch->id)->upcoming()->orderBy('starts_at')->get();
            foreach ($events as $i => $event) {
                if ($i % 2 === 1) {
                    continue; // leave every other event fully empty
                }

                foreach ($roles as $name => $role) {
                    // Fill one seat per role, so Duty role C keeps an empty seat.
                    $member = $members[$n % $members->count()];

                    try {
                        $assignment = $roster->assign($event, $role, $member, $head);
                    } catch (RosterRuleViolation) {
                        continue;
                    }

                    $answer = match ($n % 3) {
                        1 => DutyStatus::Accepted,
                        2 => DutyStatus::Declined,
                        default => null, // stays assigned
                    };
                    $n++;

                    if ($answer) {
                        try {
                            $roster->respond($assignment, $member, $answer);
                        } catch (RosterRuleViolation) {
                            // skip
                        }
                    }
                }

                // One demo guest per branch on the first event; Duty role C has a spare seat.
                if ($i === 0) {
                    try {
                        $roster->assignGuest($event, $roles['Duty role C'], 'Guest Volunteer', $head);
                    } catch (RosterRuleViolation) {
                        // no room, skip
                    }
                }
            }
        }
    }
}
