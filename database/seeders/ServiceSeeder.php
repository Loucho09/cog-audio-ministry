<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Demo/initial content seeder. Uses firstOrCreate so re-running never
 * overwrites edits made in Filament.
 */
class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $main = Branch::main()->firstOrFail();

        $schedule = [
            [2, '19:00', 'Revival Night'],
            [3, '19:00', 'Salvation Night'],
            [6, '17:30', 'Empowered Night'],
        ];
        foreach (['05:30', '07:30', '11:30', '13:30', '15:30', '17:30', '19:30'] as $time) {
            $schedule[] = [0, $time, 'Sunday Service'];
        }

        foreach ($schedule as $i => [$day, $time, $name]) {
            Service::firstOrCreate(
                ['branch_id' => $main->id, 'day_of_week' => $day, 'start_time' => $time.':00'],
                ['name' => $name, 'is_active' => true, 'sort_order' => $i + 1],
            );
        }
    }
}
