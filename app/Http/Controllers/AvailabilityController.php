<?php

namespace App\Http\Controllers;

use App\Enums\AvailabilityPart;
use App\Exceptions\RosterRuleViolation;
use App\Http\Requests\StoreAvailabilityRequest;
use App\Models\MemberUnavailability;
use App\Services\AvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AvailabilityController extends Controller
{
    public function __construct(private AvailabilityService $availability) {}

    public function store(StoreAvailabilityRequest $request): RedirectResponse
    {
        Gate::authorize('create', MemberUnavailability::class);

        $data = $request->validated();

        try {
            $this->availability->mark(
                $request->user(),
                $data['date'],
                AvailabilityPart::from($data['part']),
                $data['note'] ?? null,
            );
        } catch (RosterRuleViolation $e) {
            return redirect()->route('dashboard')->withInput()->with('availability_error', $e->getMessage());
        }

        return redirect()->route('dashboard')->with('status', 'Got it. Your admin will see you are unavailable.');
    }

    public function destroy(MemberUnavailability $unavailability): RedirectResponse
    {
        Gate::authorize('delete', $unavailability);

        try {
            $this->availability->remove($unavailability);
        } catch (RosterRuleViolation $e) {
            return redirect()->route('dashboard')->with('availability_error', $e->getMessage());
        }

        return redirect()->route('dashboard')->with('status', 'Removed. Thank you for updating us.');
    }
}
