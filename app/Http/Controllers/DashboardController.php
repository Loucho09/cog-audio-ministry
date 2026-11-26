<?php

namespace App\Http\Controllers;

use App\Actions\AdminDashboardData;
use App\Actions\UpcomingServices;
use App\Enums\AvailabilityPart;
use App\Enums\DutyStatus;
use App\Filament\Resources\DutySwaps\DutySwapResource;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\JoinApplications\JoinApplicationResource;
use App\Models\Branch;
use App\Models\DutyAssignment;
use App\Models\DutySwap;
use App\Models\MemberUnavailability;
use App\Services\DutyRosterService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DutyRosterService $roster, private AdminDashboardData $adminData) {}

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

        // Admins manage the roster and never serve, so they get their own dashboard.
        if ($user->isAdminRole()) {
            return view('dashboard-admin', [
                'greeting' => $greeting,
                'groups' => $this->adminData->for($user),
                'isSuper' => $user->hasRole('super-admin'),
                'links' => [
                    'event' => EventResource::getUrl('create'),
                    'events' => EventResource::getUrl('index'),
                    'applications' => JoinApplicationResource::getUrl('index'),
                    'swaps' => DutySwapResource::getUrl('index'),
                ],
            ]);
        }

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

        // Load the user's active duties and upcoming unavailability once, then filter in PHP.
        // Rules mirror DutyRosterService::isServingDuring (time overlap on active duties) and
        // MemberUnavailability::blocks (event date + whole day or the event's AM/PM block).
        $activeDuties = DutyAssignment::query()
            ->active()
            ->where('user_id', $user->id)
            ->whereHas('event', fn ($q) => $q->where('ends_at', '>', now()))
            ->with('event')
            ->get();

        $unavailability = MemberUnavailability::query()->where('user_id', $user->id)->upcoming()->orderBy('date')->get();

        $openSwaps = DutySwap::query()
            ->claimableBy($user)
            ->with(['assignment.event', 'assignment.dutyRole', 'offeredBy'])
            ->get()
            ->filter(function (DutySwap $swap) use ($activeDuties, $unavailability) {
                $event = $swap->assignment->event;

                $serving = $activeDuties->contains(fn (DutyAssignment $duty) => $duty->event->starts_at->lt($event->ends_at)
                    && $duty->event->ends_at->gt($event->starts_at));

                if ($serving) {
                    return false;
                }

                $date = $event->starts_at->copy()->setTimezone(config('app.timezone'))->toDateString();
                $block = $event->block();

                return ! $unavailability->contains(fn (MemberUnavailability $entry) => $entry->date->toDateString() === $date
                    && in_array($entry->part, [AvailabilityPart::WholeDay, $block], true));
            })
            ->sortBy(fn (DutySwap $swap) => $swap->assignment->event->starts_at)
            ->values();

        $claimedSwaps = DutySwap::query()
            ->live()
            ->where('claimed_by', $user->id)
            ->with(['assignment.event', 'assignment.dutyRole', 'offeredBy'])
            ->get()
            ->sortBy(fn (DutySwap $swap) => $swap->assignment->event->starts_at)
            ->values();

        $availability = $user->can('create', MemberUnavailability::class) ? $unavailability : collect();

        // Duties that can still be answered come first, then the rest by what needs attention, then by date.
        $needsReply = $duties
            ->filter(fn (DutyAssignment $duty) => $duty->status === DutyStatus::Assigned && ! $duty->event->hasStarted())
            ->values();

        $upcomingDuties = $duties
            ->reject(fn (DutyAssignment $duty) => $duty->status === DutyStatus::Declined || $needsReply->contains($duty))
            ->sortBy(fn (DutyAssignment $duty) => [
                match ($duty->status) {
                    DutyStatus::Assigned => 0,
                    DutyStatus::SwapRequested => 1,
                    default => 2,
                },
                $duty->event->starts_at->getTimestamp(),
            ])
            ->values();

        $declinedDuties = $duties
            ->filter(fn (DutyAssignment $duty) => $duty->status === DutyStatus::Declined)
            ->values();

        return view('dashboard', [
            'availability' => $availability,
            'duties' => $duties,
            'needsReply' => $needsReply,
            'upcomingDuties' => $upcomingDuties,
            'declinedDuties' => $declinedDuties,
            'openSwaps' => $openSwaps,
            'claimedSwaps' => $claimedSwaps,
            'greeting' => $greeting,
            'branch' => $branch,
            'serviceGroups' => $serviceGroups,
            'isAdmin' => $user->hasAdminAccess(),
        ]);
    }
}
