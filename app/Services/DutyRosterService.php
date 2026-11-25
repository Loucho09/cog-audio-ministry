<?php

namespace App\Services;

use App\Enums\DutyStatus;
use App\Exceptions\RosterRuleViolation;
use App\Models\Branch;
use App\Models\DutyAssignment;
use App\Models\DutyRole;
use App\Models\DutySwap;
use App\Models\Event;
use App\Models\EventDutySlot;
use App\Models\MemberUnavailability;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DutyRosterService
{
    public function assign(Event $event, DutyRole $role, User $member, ?User $by = null): DutyAssignment
    {
        return DB::transaction(function () use ($event, $role, $member, $by) {
            // Lock order everywhere: event, then users, then slots. Re-read fresh values inside the locks.
            $event = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            $member = User::query()->whereKey($member->id)->lockForUpdate()->firstOrFail();

            $slot = EventDutySlot::query()
                ->where('event_id', $event->id)
                ->where('duty_role_id', $role->id)
                ->lockForUpdate()
                ->first();

            if ($event->hasStarted()) {
                throw new RosterRuleViolation('This event has already started, so duties can no longer be assigned.');
            }

            if ((int) $member->branch_id !== (int) $event->branch_id) {
                throw new RosterRuleViolation('This member belongs to a different branch.');
            }

            if (! $member->hasAnyRole(['member', 'branch-admin'])) {
                throw new RosterRuleViolation('This person cannot be given duties.');
            }

            if ((int) $role->branch_id !== (int) $event->branch_id) {
                throw new RosterRuleViolation('That duty belongs to a different branch.');
            }

            if (! $slot) {
                throw new RosterRuleViolation('This event has no slot for that duty.');
            }

            $declined = DutyAssignment::query()
                ->where('event_id', $event->id)
                ->where('duty_role_id', $role->id)
                ->where('user_id', $member->id)
                ->where('status', DutyStatus::Declined)
                ->exists();

            if ($declined) {
                throw new RosterRuleViolation('This member declined this duty, so it cannot be assigned again.');
            }

            if ($this->isServingDuring($member, $event)) {
                throw new RosterRuleViolation('This member is already serving at that time.');
            }

            if (MemberUnavailability::blocks($member, $event)) {
                throw new RosterRuleViolation("{$member->name} marked this time as unavailable.");
            }

            $filled = DutyAssignment::query()
                ->active()
                ->where('event_id', $event->id)
                ->where('duty_role_id', $role->id)
                ->count();

            if ($filled >= $slot->quantity) {
                throw new RosterRuleViolation('That duty is already full for this event.');
            }

            return DutyAssignment::create([
                'branch_id' => $event->branch_id,
                'event_id' => $event->id,
                'duty_role_id' => $role->id,
                'user_id' => $member->id,
                'assigned_by' => $by?->id,
                'status' => DutyStatus::Assigned,
            ]);
        });
    }

    /** True when the member has an active duty on an event overlapping this one. */
    public function isServingDuring(User $member, Event $event): bool
    {
        return DutyAssignment::query()
            ->active()
            ->where('user_id', $member->id)
            ->whereHas('event', fn ($q) => $q
                ->where('starts_at', '<', $event->ends_at)
                ->where('ends_at', '>', $event->starts_at))
            ->exists();
    }

    public function assignGuest(Event $event, DutyRole $role, string $name, ?User $by = null): DutyAssignment
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        $length = mb_strlen($name);

        if ($length < 2 || $length > 100) {
            throw new RosterRuleViolation('Please enter the name of the guest (2 to 100 characters).');
        }

        return DB::transaction(function () use ($event, $role, $name, $by) {
            // Branch lock is taken first (before event/slot locks) so two admins adding the same guest
            // name to overlapping events of one branch are serialized. An event never changes branch.
            Branch::query()->whereKey($event->branch_id)->lockForUpdate()->first();

            $event = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            $slot = EventDutySlot::query()
                ->where('event_id', $event->id)
                ->where('duty_role_id', $role->id)
                ->lockForUpdate()
                ->first();

            if ($event->hasStarted()) {
                throw new RosterRuleViolation('This event has already started, so duties can no longer be assigned.');
            }

            if (! $slot) {
                throw new RosterRuleViolation('This event has no slot for that duty.');
            }

            // Name match is case-insensitive, and on MySQL also accent-insensitive via collation
            // (José = Jose). That is intended: treat them as the same person.
            $overlaps = DutyAssignment::query()
                ->active()
                ->whereNull('user_id')
                ->where('branch_id', $event->branch_id)
                ->whereRaw('LOWER(guest_name) = ?', [mb_strtolower($name)])
                ->whereHas('event', fn ($q) => $q
                    ->where('starts_at', '<', $event->ends_at)
                    ->where('ends_at', '>', $event->starts_at))
                ->exists();

            if ($overlaps) {
                throw new RosterRuleViolation('A guest with this name is already serving at that time.');
            }

            $filled = DutyAssignment::query()
                ->active()
                ->where('event_id', $event->id)
                ->where('duty_role_id', $role->id)
                ->count();

            if ($filled >= $slot->quantity) {
                throw new RosterRuleViolation('That duty is already full for this event.');
            }

            return DutyAssignment::create([
                'branch_id' => $event->branch_id,
                'event_id' => $event->id,
                'duty_role_id' => $role->id,
                'user_id' => null,
                'guest_name' => $name,
                'assigned_by' => $by?->id,
                'status' => DutyStatus::Accepted,
                'responded_at' => now(),
            ]);
        });
    }

    public function remove(DutyAssignment $assignment): void
    {
        DB::transaction(function () use ($assignment) {
            // Lock order: swaps, then assignment (same as DutySwapService::cancel).
            DutySwap::query()->where('duty_assignment_id', $assignment->id)->lockForUpdate()->get();
            $current = DutyAssignment::query()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();

            if ($current->event->hasStarted()) {
                throw new RosterRuleViolation('This event has already started, so its duties are kept as a record.');
            }

            if ($current->status === DutyStatus::Declined) {
                throw new RosterRuleViolation('A declined duty is kept as a record and cannot be removed.');
            }

            if ($current->status === DutyStatus::Swapped) {
                throw new RosterRuleViolation("This duty was swapped and can't be removed.");
            }

            // The duty_swaps FK cascades on delete, so open/claimed swap rows vanish with the assignment;
            // marking them Cancelled first would only write rows that are deleted right after.
            $current->delete();
        });
    }

    public function respond(DutyAssignment $assignment, User $member, DutyStatus $answer): DutyAssignment
    {
        if (! in_array($answer, [DutyStatus::Accepted, DutyStatus::Declined], true)) {
            throw new RosterRuleViolation('You can only accept or decline a duty.');
        }

        return DB::transaction(function () use ($assignment, $member, $answer) {
            $current = DutyAssignment::query()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();

            if ($current->user_id === null || (int) $current->user_id !== (int) $member->id) {
                throw new RosterRuleViolation('This duty is not yours to answer.');
            }

            if ($current->status !== DutyStatus::Assigned) {
                throw new RosterRuleViolation('You have already responded to this duty.');
            }

            if ($current->event->hasStarted()) {
                throw new RosterRuleViolation('This event has already started, so you can no longer respond.');
            }

            $current->update(['status' => $answer, 'responded_at' => now()]);

            return $current;
        });
    }

    /**
     * Saves event details, times and the full desired slot set atomically.
     * Locks the event row, then the active members' user rows (by id), then checks the rules.
     * Use this instead of calling the assert methods yourself.
     *
     * @param  array<string, mixed>  $attributes  name, starts_at, ends_at, notes, service_id
     * @param  array<int, int>  $quantitiesByRoleId  [dutyRoleId => quantity]; roles left out are removed.
     */
    public function updateEvent(Event $event, array $attributes, array $quantitiesByRoleId): Event
    {
        $attributes = Arr::only($attributes, ['name', 'starts_at', 'ends_at', 'notes', 'service_id']);

        return DB::transaction(function () use ($event, $attributes, $quantitiesByRoleId) {
            $event = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            if ($event->hasStarted()) {
                throw new RosterRuleViolation('This event has already started and can no longer be changed.');
            }

            $memberIds = DutyAssignment::query()->active()->where('event_id', $event->id)
                ->whereNotNull('user_id')->pluck('user_id')->unique()->sort()->values();
            User::query()->whereIn('id', $memberIds)->orderBy('id')->lockForUpdate()->get();

            $startsAt = Carbon::parse($attributes['starts_at'] ?? $event->starts_at);
            $endsAt = Carbon::parse($attributes['ends_at'] ?? $event->ends_at);

            if ($startsAt->notEqualTo($event->starts_at) && $startsAt->lessThanOrEqualTo(now())) {
                throw new RosterRuleViolation('Pick a start time in the future.');
            }

            $this->assertTimeChangeAllowed($event, $startsAt, $endsAt);
            $this->assertSlotChangesAllowed($event, $quantitiesByRoleId);

            $event->update($attributes);

            foreach ($quantitiesByRoleId as $roleId => $quantity) {
                EventDutySlot::query()->updateOrCreate(
                    ['event_id' => $event->id, 'duty_role_id' => $roleId],
                    ['quantity' => $quantity],
                );
            }

            EventDutySlot::query()
                ->where('event_id', $event->id)
                ->whereNotIn('duty_role_id', array_keys($quantitiesByRoleId))
                ->delete();

            return $event->fresh();
        });
    }

    /**
     * Does not lock on its own; call it through updateEvent() so the check and save are atomic.
     *
     * @param  array<int, int>  $quantitiesByRoleId  [dutyRoleId => quantity]; a missing role id means the slot is removed.
     */
    public function assertSlotChangesAllowed(Event $event, array $quantitiesByRoleId): void
    {
        $filled = DutyAssignment::query()
            ->active()
            ->where('event_id', $event->id)
            ->selectRaw('duty_role_id, count(*) as total')
            ->groupBy('duty_role_id')
            ->pluck('total', 'duty_role_id');

        foreach ($filled as $roleId => $total) {
            $newQuantity = (int) ($quantitiesByRoleId[$roleId] ?? 0);

            if ($newQuantity < (int) $total) {
                throw new RosterRuleViolation('A duty already filled by members cannot be reduced below the number of people serving. Remove them first.');
            }
        }
    }

    /** Does not lock on its own; call it through updateEvent() so the check and save are atomic. */
    public function assertTimeChangeAllowed(Event $event, CarbonInterface $startsAt, CarbonInterface $endsAt): void
    {
        if (! $endsAt->greaterThan($startsAt)) {
            throw new RosterRuleViolation('The end time must be after the start time.');
        }

        $userIds = DutyAssignment::query()
            ->active()
            ->where('event_id', $event->id)
            ->whereNotNull('user_id')
            ->pluck('user_id');

        $guestNames = DutyAssignment::query()
            ->active()
            ->where('event_id', $event->id)
            ->whereNull('user_id')
            ->whereNotNull('guest_name')
            ->pluck('guest_name')
            ->map(fn ($n) => mb_strtolower($n))
            ->unique()
            ->values();

        $guestClash = $guestNames->isNotEmpty() && DutyAssignment::query()
            ->active()
            ->whereNull('user_id')
            ->where('branch_id', $event->branch_id)
            ->where('event_id', '!=', $event->id)
            ->whereIn(DB::raw('LOWER(guest_name)'), $guestNames->all())
            ->whereHas('event', fn ($q) => $q
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt))
            ->exists();

        if ($guestClash) {
            throw new RosterRuleViolation('A guest on this event is already serving at the new time.');
        }

        $clash = DutyAssignment::query()
            ->active()
            ->whereIn('user_id', $userIds)
            ->where('event_id', '!=', $event->id)
            ->whereHas('event', fn ($q) => $q
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt))
            ->exists();

        if ($clash) {
            throw new RosterRuleViolation('A member on this event is already serving at the new time.');
        }
    }
}
