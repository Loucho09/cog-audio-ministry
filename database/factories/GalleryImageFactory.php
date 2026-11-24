<?php

namespace Database\Factories;

use App\Models\GalleryImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GalleryImage> */
class GalleryImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'path' => 'images/placeholders/gallery-1.svg',
            'alt_text' => 'Photo from COG Audio Ministry',
            'is_published' => true,
        ];
    }
}
