<?php

use App\Models\GalleryImage;
use App\Support\GalleryThumbnail;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backfill: new and replaced photos get a thumbnail automatically; run this once for older ones.
Artisan::command('gallery:thumbnails', function () {
    $made = 0;

    GalleryImage::query()->withoutGlobalScopes()->whereNotNull('path')->each(function (GalleryImage $image) use (&$made) {
        $made += GalleryThumbnail::generate($image->path) ? 1 : 0;
    });

    $this->info("Gallery thumbnails created: {$made}.");
})->purpose('Create small grid copies of gallery photos');
