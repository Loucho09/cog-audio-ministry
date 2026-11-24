<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Demo/initial content seeder. Uses firstOrCreate so re-running never
 * overwrites edits made in the admin panel.
 */
class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $main = Branch::main()->firstOrFail();

        // Owner 2026-11-24: Revival Night, Audio Fellowship and Salvation Night paused,
        // resume first week of Jan 2027. Audio Fellowship is public, before Salvation Night.
        $schedule = [
            [2, '19:00', 'Revival Night', '2027-01-05'],
            [3, '18:00', 'Audio Fellowship', '2027-01-06'],
            [3, '19:00', 'Salvation Night', '2027-01-06'],
            [6, '17:30', 'Empowered Night', null],
        ];
        foreach (['05:30', '07:30', '11:30', '13:30', '15:30', '17:30', '19:30'] as $time) {
            $schedule[] = [0, $time, 'Sunday Service', null];
        }

        foreach ($schedule as $i => [$day, $time, $name, $startsOn]) {
            Service::firstOrCreate(
                ['branch_id' => $main->id, 'day_of_week' => $day, 'start_time' => $time.':00'],
                ['name' => $name, 'is_active' => true, 'sort_order' => $i + 1, 'starts_on' => $startsOn, 'ends_on' => null],
            );
        }
    }
}
