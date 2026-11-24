<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        // TODO: address and contact details pending from the Audio Head.
        $branches = [
            ['name' => 'COG Dasmariñas', 'slug' => 'dasma', 'is_main' => true],
            ['name' => 'COG Marilag', 'slug' => 'marilag', 'is_main' => false],
            ['name' => 'COG Trece', 'slug' => 'trece', 'is_main' => false],
            ['name' => 'COG Silang', 'slug' => 'silang', 'is_main' => false],
            ['name' => 'COG Jabez', 'slug' => 'jabez', 'is_main' => false],
        ];

        foreach ($branches as $data) {
            $branch = Branch::firstOrNew(['slug' => $data['slug']]);
            $branch->forceFill($data)->save();
        }
    }
}
