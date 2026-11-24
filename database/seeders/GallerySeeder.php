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
    /** Captions for the bundled photos: filename => [title, alt text]. */
    private const CAPTIONS = [
        '01-team-on-stage.webp' => ['Team on stage', 'The audio and production team smiling together on stage in front of a big LED screen showing the number 36'],
        '02-sound-console.webp' => ['At the sound console', 'View from the sound console during a worship service, with the stage and a full auditorium ahead'],
        '03-fellowship-meal.webp' => ['Fellowship meal', 'Ministry members gathered around a long table of food served on banana leaves'],
        '04-outreach-day.webp' => ['Outreach day', 'Smiling volunteers holding up gospel cards at an outdoor outreach booth'],
        '05-sharing-together.webp' => ['Sharing together', 'Two members sitting under purple stage lights, one sharing while the other listens'],
        '06-behind-the-scenes.webp' => ['Behind the scenes', 'A member in profile under warm red stage lighting'],
    ];

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
                $name = basename($file);
                [$title, $alt] = self::CAPTIONS[$name] ?? [null, 'Photo from COG Audio Ministry'];

                GalleryImage::firstOrCreate(
                    ['path' => 'images/gallery/'.$name],
                    ['title' => $title, 'alt_text' => $alt, 'sort_order' => $i + 1, 'is_published' => true],
                );
            }

            // Real photos exist: hide (never delete) old placeholder rows.
            GalleryImage::query()->where('path', 'like', 'images/placeholders/%')->update(['is_published' => false]);

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
