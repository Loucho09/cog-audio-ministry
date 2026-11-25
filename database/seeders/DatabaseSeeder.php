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

        $this->call([
            ...$seeders,
            ServiceSeeder::class,
            GallerySeeder::class,
            TeamMemberSeeder::class,
        ]);
    }
}
