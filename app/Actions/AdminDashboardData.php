<?php

namespace App\Actions;

use App\Enums\DutyStatus;
use App\Enums\SwapStatus;
use App\Filament\Resources\Events\EventResource;
use App\Models\Branch;
use App\Models\DutyAssignment;
use App\Models\DutySwap;
use App\Models\Event;
use App\Models\EventDutySlot;
use App\Models\JoinApplication;
use App\Models\MemberUnavailability;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Everything the admin dashboard shows, one group per branch.
 * A branch-admin gets only their own branch; the super-admin gets every branch.
 * Each query runs once for all branches in scope, then is grouped in PHP (no per-branch queries).
 */
class AdminDashboardData
{
    public const WINDOW_DAYS = 14;

    public const WAITING_LIMIT = 10;

    /** @return Collection<int, array<string, mixed>> */
    public function for(User $admin): Collection
    {
        $branches = Branch::query()
            ->with(['services' => fn ($q) => $q->active()->ordered()])
            ->when(! $admin->hasRole('super-admin'), fn ($q) => $q->whereKey($admin->branch_id))
            ->orderBy('name')
            ->get();

        if ($branches->isEmpty()) {
            return collect();
        }

        $ids = $branches->pluck('id')->all();
        $now = now();
        $until = $now->copy()->addDays(self::WINDOW_DAYS);

        $pending = JoinApplication::query()->pending()->whereIn('branch_id', $ids)
            ->selectRaw('branch_id, count(*) as total')->groupBy('branch_id')->pluck('total', 'branch_id');

        $swapsToApprove = DutySwap::query()->live()
            ->where('status', SwapStatus::Claimed->value)->whereIn('branch_id', $ids)
            ->selectRaw('branch_id, count(*) as total')->groupBy('branch_id')->pluck('total', 'branch_id');

        $events = Event::query()
            ->whereIn('branch_id', $ids)
            ->where('starts_at', '>', $now)->where('starts_at', '<=', $until)
            ->with(['dutySlots.dutyRole'])
            ->orderBy('starts_at')
            ->get();

        $declinedRows = DutyAssignment::query()->declined()->whereIn('branch_id', $ids)
            ->whereHas('event', fn ($q) => $q->where('starts_at', '>', $now))
            ->get(['id', 'branch_id', 'event_id', 'duty_role_id']);

        $slots = EventDutySlot::query()->whereIn('event_id', $declinedRows->pluck('event_id'))->get()
            ->keyBy(fn ($slot) => $slot->event_id.'-'.$slot->duty_role_id);

        $filled = $this->withoutAdmins(DutyAssignment::query()->active())
            ->whereIn('event_id', $events->pluck('id')->merge($declinedRows->pluck('event_id'))->unique()->values())
            ->selectRaw('event_id, duty_role_id, count(*) as total')
            ->groupBy('event_id', 'duty_role_id')->get()
            ->groupBy('event_id');

        // A declined seat only needs refilling while its slot is still short.
        $declined = $declinedRows->filter(function ($row) use ($slots, $filled) {
            $slot = $slots->get($row->event_id.'-'.$row->duty_role_id);
            $count = ($filled->get($row->event_id) ?? collect())->firstWhere('duty_role_id', $row->duty_role_id)?->total ?? 0;

            return $slot && (int) $count < $slot->quantity;
        })->countBy('branch_id');

        $waiting = $this->withoutAdmins(DutyAssignment::query())
            ->select('duty_assignments.*')
            ->join('events', 'events.id', '=', 'duty_assignments.event_id')
            ->whereIn('duty_assignments.branch_id', $ids)
            ->where('duty_assignments.status', DutyStatus::Assigned->value)
            ->whereNotNull('duty_assignments.user_id')
            ->where('events.starts_at', '>', $now)->where('events.starts_at', '<=', $until)
            ->with(['user', 'dutyRole', 'event'])
            ->orderBy('events.starts_at')->orderBy('duty_assignments.id')
            ->get()
            ->groupBy('branch_id');

        $unavailable = MemberUnavailability::query()
            ->whereIn('branch_id', $ids)
            ->whereDate('date', '>=', $now->toDateString())
            ->whereDate('date', '<=', $until->toDateString())
            ->with('user')
            ->orderBy('date')->orderBy('id')
            ->get()
            ->groupBy('branch_id');

        $memberCounts = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'member'))->whereIn('branch_id', $ids)
            ->selectRaw('branch_id, count(*) as total')->groupBy('branch_id')->pluck('total', 'branch_id');

        $responses = DutyAssignment::query()
            ->whereIn('branch_id', $ids)
            ->whereNotNull('user_id')
            ->whereIn('status', [DutyStatus::Accepted->value, DutyStatus::Declined->value])
            ->where('responded_at', '>=', $now->copy()->subDays(30))
            ->selectRaw('branch_id, status, count(*) as total')->groupBy('branch_id', 'status')->get()
            ->groupBy('branch_id');

        return $branches->map(function (Branch $branch) use ($pending, $swapsToApprove, $declined, $events, $filled, $waiting, $unavailable, $memberCounts, $responses, $now) {
            $answers = ($responses->get($branch->id) ?? collect())->mapWithKeys(fn ($row) => [$row->status->value => $row->total]);

            return [
                'branch' => $branch,
                'attention' => [
                    'applications' => (int) ($pending[$branch->id] ?? 0),
                    'swaps' => (int) ($swapsToApprove[$branch->id] ?? 0),
                    'declined' => (int) ($declined[$branch->id] ?? 0),
                ],
                'unfilled' => $this->unfilled($events->where('branch_id', $branch->id), $filled),
                'waiting' => ($waiting->get($branch->id) ?? collect())->take(self::WAITING_LIMIT)->values(),
                'unavailable' => ($unavailable->get($branch->id) ?? collect())->values(),
                'snapshot' => [
                    'members' => (int) ($memberCounts[$branch->id] ?? 0),
                    'accepted' => (int) ($answers[DutyStatus::Accepted->value] ?? 0),
                    'declined' => (int) ($answers[DutyStatus::Declined->value] ?? 0),
                    'services' => UpcomingServices::fromServices($branch->services, 1, $now),
                ],
            ];
        })->values();
    }

    /** Legacy rows held by admins are ignored: admins never serve. Guests (no user) stay. */
    private function withoutAdmins(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('duty_assignments.user_id')
            ->orWhereDoesntHave('user.roles', fn ($r) => $r->whereIn('name', ['branch-admin', 'super-admin'])));
    }

    /**
     * @param  Collection<int, Event>  $events
     * @param  Collection<int, Collection>  $filled  keyed by event id
     * @return Collection<int, array{event: Event, url: string, gaps: array<int, string>}>
     */
    private function unfilled(Collection $events, Collection $filled): Collection
    {
        return $events->map(function (Event $event) use ($filled) {
            $counts = ($filled->get($event->id) ?? collect())->pluck('total', 'duty_role_id');

            $gaps = $event->dutySlots
                ->filter(fn ($slot) => (int) ($counts[$slot->duty_role_id] ?? 0) < $slot->quantity)
                ->sortBy(fn ($slot) => $slot->dutyRole?->sort_order ?? 0)
                ->map(fn ($slot) => ($slot->dutyRole?->name ?? 'Duty').' '.(int) ($counts[$slot->duty_role_id] ?? 0).' of '.$slot->quantity)
                ->values()
                ->all();

            return ['event' => $event, 'url' => EventResource::getUrl('view', ['record' => $event]), 'gaps' => $gaps];
        })->filter(fn (array $row) => $row['gaps'] !== [])->values();
    }
}
