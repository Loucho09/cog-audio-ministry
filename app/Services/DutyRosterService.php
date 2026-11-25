<?php

namespace App\Services;

use App\Enums\DutyStatus;
use App\Exceptions\RosterRuleViolation;
use App\Models\DutyAssignment;
use App\Models\DutyRole;
use App\Models\Event;
use App\Models\EventDutySlot;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DutyRosterService
{
    public function assign(Event $event, DutyRole $role, User $member, ?User $by = null): DutyAssignment
    {
        return DB::transaction(function () use ($event, $role, $member, $by) {
            $slot = EventDutySlot::query()
                ->where('event_id', $event->id)
                ->where('duty_role_id', $role->id)
                ->lockForUpdate()
                ->first();

            if ($event->hasStarted()) {
                throw new RosterRuleViolation('This event has already started, so duties can no longer be assigned.');
            }

            if ($member->branch_id !== $event->branch_id) {
                throw new RosterRuleViolation('This member belongs to a different branch.');
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

            $overlaps = DutyAssignment::query()
                ->active()
                ->where('user_id', $member->id)
                ->whereHas('event', fn ($q) => $q
                    ->where('starts_at', '<', $event->ends_at)
                    ->where('ends_at', '>', $event->starts_at))
                ->exists();

            if ($overlaps) {
                throw new RosterRuleViolation('This member is already serving at that time.');
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

    public function remove(DutyAssignment $assignment): void
    {
        if ($assignment->status === DutyStatus::Declined) {
            throw new RosterRuleViolation('A declined duty is kept as a record and cannot be removed.');
        }

        $assignment->delete();
    }
}
