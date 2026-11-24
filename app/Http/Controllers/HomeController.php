<?php

namespace App\Http\Controllers;

use App\Actions\UpcomingServices;
use App\Models\Branch;
use App\Models\GalleryImage;
use App\Models\Service;
use App\Models\TeamMember;
use App\Support\ServiceSchedule;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $now = now()->toImmutable()->setTimezone(config('app.timezone'));
        $branch = Branch::mainBranch();

        $occurrences = $branch ? UpcomingServices::forBranch($branch, 4, $now) : collect();
        $schedule = ServiceSchedule::build($occurrences, $now, $branch);

        // Gallery is intentionally not branch-filtered in Phase 1 (see brain Backlog).
        $galleryImages = GalleryImage::query()
            ->published()
            ->limit(12)
            ->get()
            ->filter(fn (GalleryImage $image) => $image->url !== null)
            ->map(fn (GalleryImage $image) => [
                'url' => $image->url,
                'alt' => $image->alt_text,
                'title' => $image->title,
                'placeholder' => $image->isPlaceholder(),
            ])
            ->values();

        $teamMembers = config('ministry.show_team') ? TeamMember::query()->featured()->get() : collect();

        // Weekly services of the main branch (one count query; occurrences above are capped at 4).
        $servicesPerWeek = $branch ? Service::query()->active()->where('branch_id', $branch->id)->count() : 0;

        return view('welcome', [
            'servicesPerWeek' => $servicesPerWeek,
            'branch' => $branch,
            'schedule' => $schedule,
            'galleryImages' => $galleryImages,
            'teamMembers' => $teamMembers,
        ]);
    }
}
