<?php

namespace Database\Seeders;

use App\Models\GalleryImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Demo/initial content seeder. Uses firstOrCreate so re-running never
 * overwrites edits made in Filament.
 */
class GallerySeeder extends Seeder
{
    public function run(): void
    {
        $dir = public_path('images/gallery');
        $files = File::isDirectory($dir)
            ? collect(File::files($dir))
                ->filter(fn ($f) => in_array(strtolower($f->getExtension()), ['jpg', 'jpeg', 'png', 'webp'], true))
                ->map(fn ($f) => $f->getPathname())
                ->sort()
                ->values()
                ->all()
            : [];

        if ($files) {
            foreach ($files as $i => $file) {
                GalleryImage::firstOrCreate(
                    ['path' => 'images/gallery/'.basename($file)],
                    // TODO: replace with real alt text describing each photo.
                    ['alt_text' => 'Photo from COG Audio Ministry', 'sort_order' => $i + 1, 'is_published' => true],
                );
            }

            return;
        }

        foreach (range(1, 6) as $n) {
            GalleryImage::firstOrCreate(
                ['path' => "images/placeholders/gallery-{$n}.svg"],
                ['alt_text' => 'Placeholder photo, real photos coming soon', 'sort_order' => $n, 'is_published' => true],
            );
        }
    }
}
