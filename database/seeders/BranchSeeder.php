<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        // TODO: full addresses and contact details pending from the Audio Head.
        // Marilag is the subdivision where COG Dasma is, not a separate branch.
        $branches = [
            ['name' => 'COG Dasmariñas', 'slug' => 'dasma', 'is_main' => true, 'facebook_url' => 'https://www.facebook.com/COGAudioDasma', 'address' => 'Marilag Subdivision, Dasmariñas'],
            ['name' => 'COG Trece', 'slug' => 'trece', 'is_main' => false, 'facebook_url' => 'https://www.facebook.com/COGTreceMartires', 'address' => null],
            ['name' => 'COG Silang', 'slug' => 'silang', 'is_main' => false, 'facebook_url' => 'https://www.facebook.com/cogsilang', 'address' => null],
            ['name' => 'COG Jabez', 'slug' => 'jabez', 'is_main' => false, 'facebook_url' => 'https://www.facebook.com/churchofgodjabez', 'address' => null],
        ];

        foreach ($branches as $data) {
            $defaults = ['facebook_url' => $data['facebook_url'], 'address' => $data['address']];
            unset($data['facebook_url'], $data['address']);

            $branch = Branch::firstOrNew(['slug' => $data['slug']]);
            $branch->forceFill($data);
            // Never overwrite an admin edit: only fill these when they are empty.
            foreach ($defaults as $column => $value) {
                if (blank($branch->{$column})) {
                    $branch->{$column} = $value;
                }
            }
            $branch->save();
        }
    }
}
