<?php

namespace App\Services;

use App\Enums\AvailabilityPart;
use App\Exceptions\RosterRuleViolation;
use App\Models\DutyAssignment;
use App\Models\MemberUnavailability;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AvailabilityService
{
    public function mark(User $user, string|CarbonInterface $date, AvailabilityPart $part, ?string $note): MemberUnavailability
    {
        if ($user->branch_id === null) {
            throw new RosterRuleViolation('Your account has no branch yet. Please ask your admin.');
        }

        $tz = config('app.timezone');
        $day = Carbon::parse($date, $tz)->startOfDay();
        $today = Carbon::now($tz)->startOfDay();

        if ($day->lt($today) || $day->gt($today->copy()->addMonths(6))) {
            throw new RosterRuleViolation('Pick a date from today up to 6 months ahead.');
        }

        $note = trim((string) $note);
        $note = $note === '' ? null : $note;

        if ($note !== null && mb_strlen($note) > 100) {
            throw new RosterRuleViolation('Keep the note to 100 characters or fewer.');
        }

        return DB::transaction(function () use ($user, $day, $part, $note) {
            // Same lock as DutyRosterService::assign(), so marking and an admin assigning can't race.
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $exists = MemberUnavailability::query()
                ->where('user_id', $user->id)
                ->whereDate('date', $day->toDateString())
                ->exists();

            if ($exists) {
                throw new RosterRuleViolation('You already marked this date. Remove it first to change it.');
            }

            $onDuty = DutyAssignment::query()
                ->active()
                ->where('user_id', $user->id)
                ->whereHas('event', fn ($q) => $q
                    ->where('starts_at', '>', now())
                    ->where('starts_at', '>=', $day->copy()->startOfDay())
                    ->where('starts_at', '<=', $day->copy()->endOfDay()))
                ->with('event')
                ->get()
                ->contains(fn (DutyAssignment $a) => $part === AvailabilityPart::WholeDay || $a->event->block() === $part);

            if ($onDuty) {
                throw new RosterRuleViolation("You're on duty then. Offer a swap or ask your admin.");
            }

            return MemberUnavailability::create([
                'user_id' => $user->id,
                'branch_id' => $user->branch_id,
                'date' => $day->toDateString(),
                'part' => $part,
                'note' => $note,
            ]);
        });
    }

    public function remove(MemberUnavailability $entry): void
    {
        DB::transaction(function () use ($entry) {
            $current = MemberUnavailability::query()->whereKey($entry->id)->lockForUpdate()->firstOrFail();

            if ($current->date->copy()->startOfDay()->lt(Carbon::now(config('app.timezone'))->startOfDay())) {
                throw new RosterRuleViolation("Past dates can't be changed.");
            }

            $current->delete();
        });
    }
}
