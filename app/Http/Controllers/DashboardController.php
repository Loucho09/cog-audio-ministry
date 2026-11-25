<?php

namespace App\Http\Controllers;

use App\Actions\UpcomingServices;
use App\Enums\DutyStatus;
use App\Models\Branch;
use App\Models\DutyAssignment;
use App\Models\DutySwap;
use App\Models\MemberUnavailability;
use App\Services\DutyRosterService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DutyRosterService $roster) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $now = now()->toImmutable()->setTimezone(config('app.timezone'));
        $hour = $now->hour;

        $greeting = match (true) {
            $hour < 12 => 'Good morning',
            $hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };

        $branch = $user->branch;

        // Own branch first, then the rest by name; one occurrence per service (next week).
        $branches = Branch::query()
            ->with(['services' => fn ($q) => $q->active()->ordered()])
            ->orderBy('name')->get()
            ->sortBy(fn (Branch $b) => $b->id === $user->branch_id ? 0 : 1)
            ->values();

        $serviceGroups = $branches
            ->map(fn (Branch $b) => [
                'branch' => $b,
                'is_own' => $b->id === $user->branch_id,
                'occurrences' => UpcomingServices::fromServices($b->services, 1, $now),
            ])
            ->filter(fn (array $group) => $group['occurrences']->isNotEmpty())
            ->values();

        $duties = DutyAssignment::query()
            ->select('duty_assignments.*')
            ->join('events', 'events.id', '=', 'duty_assignments.event_id')
            ->where('duty_assignments.user_id', $user->id)
            ->whereIn('duty_assignments.status', [DutyStatus::Assigned, DutyStatus::Accepted, DutyStatus::Declined, DutyStatus::SwapRequested])
            ->where('events.ends_at', '>=', now())
            ->with(['event.branch', 'dutyRole', 'swaps' => fn ($q) => $q->live()->with('claimedBy')])
            ->orderBy('events.starts_at')
            ->get();

        // Per-item overlap/availability checks are small queries; the list is short (one branch, future events only).
        $openSwaps = DutySwap::query()
            ->claimableBy($user)
            ->with(['assignment.event', 'assignment.dutyRole', 'offeredBy'])
            ->get()
            ->filter(fn (DutySwap $swap) => ! $this->roster->isServingDuring($user, $swap->assignment->event)
                && ! MemberUnavailability::blocks($user, $swap->assignment->event))
            ->sortBy(fn (DutySwap $swap) => $swap->assignment->event->starts_at)
            ->values();

        $availability = $user->can('create', MemberUnavailability::class)
            ? MemberUnavailability::query()->where('user_id', $user->id)->upcoming()->orderBy('date')->get()
            : collect();

        return view('dashboard', [
            'availability' => $availability,
            'duties' => $duties,
            'openSwaps' => $openSwaps,
            'greeting' => $greeting,
            'branch' => $branch,
            'serviceGroups' => $serviceGroups,
            'isAdmin' => $user->hasAdminAccess(),
        ]);
    }
}
