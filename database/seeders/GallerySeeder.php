<?php

namespace Database\Seeders;

use App\Models\GalleryImage;
use Illuminate\Database\Seeder;

class GallerySeeder extends Seeder
{
    public function run(): void
    {
        $files = glob(public_path('images/gallery/*.{jpg,jpeg,png,webp}'), GLOB_BRACE) ?: [];
        sort($files);

        if ($files) {
            foreach ($files as $i => $file) {
                GalleryImage::updateOrCreate(
                    ['path' => 'images/gallery/'.basename($file)],
                    // TODO: replace with real alt text describing each photo.
                    ['alt_text' => 'Photo from COG Audio Ministry', 'sort_order' => $i + 1, 'is_published' => true],
                );
            }

            return;
        }

        foreach (range(1, 6) as $n) {
            GalleryImage::updateOrCreate(
                ['path' => "images/placeholders/gallery-{$n}.svg"],
                ['alt_text' => 'Placeholder photo, real photos coming soon', 'sort_order' => $n, 'is_published' => true],
            );
        }
    }
}
