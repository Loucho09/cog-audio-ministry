<?php

namespace App\Http\Controllers;

use App\Actions\UpcomingServices;
use App\Models\Branch;
use App\Models\GalleryImage;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $branch = Branch::mainBranch();

        $occurrences = $branch ? UpcomingServices::forBranch($branch, 4) : collect();

        $galleryImages = GalleryImage::query()
            ->published()
            ->limit(12)
            ->get()
            ->map(fn (GalleryImage $image) => [
                'url' => $image->url,
                'alt' => $image->alt_text,
                'title' => $image->title,
                'placeholder' => str_starts_with($image->path, 'images/placeholders/'),
            ])
            ->values();

        return view('welcome', [
            'branch' => $branch,
            'occurrences' => $occurrences,
            'galleryImages' => $galleryImages,
        ]);
    }
}
