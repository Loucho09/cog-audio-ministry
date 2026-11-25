<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $seeders = [BranchSeeder::class, RoleSeeder::class];

        // Demo accounts use a known password: never outside local/testing.
        if (app()->environment(['local', 'testing'])) {
            $seeders[] = DemoUserSeeder::class;
            $seeders[] = JoinApplicationSeeder::class; // after DemoUserSeeder: needs real reviewers
        }

        $afterServices = [];
        if (app()->environment(['local', 'testing'])) {
            $afterServices[] = DutyRosterSeeder::class; // needs demo users and services
        }

        $this->call([
            ...$seeders,
            ServiceSeeder::class,
            ...$afterServices,
            GallerySeeder::class,
            TeamMemberSeeder::class,
        ]);
    }
}
