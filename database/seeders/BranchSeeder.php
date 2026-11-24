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
            ['name' => 'COG Dasmariñas', 'slug' => 'dasma', 'is_main' => true, 'facebook_url' => 'https://www.facebook.com/COGAudioDasma'],
            ['name' => 'COG Marilag', 'slug' => 'marilag', 'is_main' => false, 'facebook_url' => null],
            ['name' => 'COG Trece', 'slug' => 'trece', 'is_main' => false, 'facebook_url' => 'https://www.facebook.com/COGTreceMartires'],
            ['name' => 'COG Silang', 'slug' => 'silang', 'is_main' => false, 'facebook_url' => 'https://www.facebook.com/cogsilang'],
            ['name' => 'COG Jabez', 'slug' => 'jabez', 'is_main' => false, 'facebook_url' => 'https://www.facebook.com/churchofgodjabez'],
        ];

        foreach ($branches as $data) {
            $facebookUrl = $data['facebook_url'];
            unset($data['facebook_url']);

            $branch = Branch::firstOrNew(['slug' => $data['slug']]);
            $branch->forceFill($data);
            // Never overwrite an admin edit: only fill the URL when it is empty.
            if (blank($branch->facebook_url)) {
                $branch->facebook_url = $facebookUrl;
            }
            $branch->save();
        }
    }
}
