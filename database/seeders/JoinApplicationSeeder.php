<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\JoinApplication;
use Illuminate\Database\Seeder;

/** Fake demo applications. Skips branches that already have some, so re-seeding does not duplicate. */
class JoinApplicationSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Branch::all() as $branch) {
            if (JoinApplication::where('branch_id', $branch->id)->exists()) {
                continue;
            }

            JoinApplication::factory()->pending()->count(2)->create(['branch_id' => $branch->id]);
            JoinApplication::factory()->approved()->create(['branch_id' => $branch->id]);
            JoinApplication::factory()->rejected()->create(['branch_id' => $branch->id]);
        }
    }
}
