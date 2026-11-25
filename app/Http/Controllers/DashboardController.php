<?php

namespace App\Http\Controllers;

use App\Actions\UpcomingServices;
use App\Models\Branch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $now = now()->toImmutable()->setTimezone('Asia/Manila');
        $hour = $now->hour;

        $greeting = match (true) {
            $hour < 12 => 'Good morning',
            $hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };

        $branch = $user->branch;

        // Own branch first, then the rest by name; one occurrence per service (next week).
        $branches = Branch::query()->orderBy('name')->get()
            ->sortBy(fn (Branch $b) => $b->id === $user->branch_id ? 0 : 1)
            ->values();

        $serviceGroups = $branches
            ->map(fn (Branch $b) => [
                'branch' => $b,
                'is_own' => $b->id === $user->branch_id,
                'occurrences' => UpcomingServices::forBranch($b, 1, $now),
            ])
            ->filter(fn (array $group) => $group['occurrences']->isNotEmpty())
            ->values();

        return view('dashboard', [
            'greeting' => $greeting,
            'branch' => $branch,
            'serviceGroups' => $serviceGroups,
            'isAdmin' => $user->hasAdminAccess(),
        ]);
    }
}
