<?php

namespace App\Http\Controllers;

use App\Actions\UpcomingServices;
use App\Models\Branch;
use App\Models\GalleryImage;
use App\Models\Service;
use App\Models\TeamMember;
use App\Support\GalleryThumbnail;
use App\Support\ServiceSchedule;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $now = now()->toImmutable()->setTimezone(config('app.timezone'));
        // One branches query feeds the churches list, the churches count, and the main branch.
        $churches = Branch::query()->orderByDesc('is_main')->orderBy('name')->get();
        $branch = $churches->first(fn (Branch $church) => $church->is_main);

        $occurrences = $branch ? UpcomingServices::forBranch($branch, 4, $now) : collect();
        $schedule = ServiceSchedule::build($occurrences, $now, $branch);

        // Gallery is intentionally not branch-filtered in Phase 1 (see brain Backlog).
        $thumbnails = GalleryThumbnail::index(); // one directory listing for all photos
        $galleryImages = GalleryImage::query()
            ->published()
            ->whereNotNull('path')
            ->limit(12)
            ->get()
            ->filter(fn (GalleryImage $image) => $image->url !== null)
            ->map(fn (GalleryImage $image) => [
                'url' => $image->url,
                'thumb' => Arr::first(GalleryThumbnail::variants($image->path, $thumbnails)),
                'srcset' => collect(GalleryThumbnail::variants($image->path, $thumbnails))->map(fn (string $url, int $width) => "{$url} {$width}w")->implode(', '),
                // Never empty: alt text, then the title, then a generic label (keeps aria-labels readable).
                'alt' => trim((string) $image->alt_text) ?: trim((string) $image->title) ?: 'Audio Ministry photo',
                'title' => $image->title,
                'placeholder' => $image->isPlaceholder(),
            ])
            ->values();

        $teamMembers = config('ministry.show_team') ? TeamMember::query()->published()->featured()->get() : collect();

        // Weekly services of the main branch (one count query; occurrences above are capped at 4).
        $servicesPerWeek = $branch ? Service::query()->active()->where('branch_id', $branch->id)->count() : 0;

        return view('welcome', [
            'servicesPerWeek' => $servicesPerWeek,
            'churches' => $churches,
            'branch' => $branch,
            'schedule' => $schedule,
            'galleryImages' => $galleryImages,
            'teamMembers' => $teamMembers,
        ]);
    }
}
