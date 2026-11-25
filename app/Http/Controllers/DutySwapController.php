<?php

namespace App\Http\Controllers;

use App\Exceptions\RosterRuleViolation;
use App\Models\DutyAssignment;
use App\Models\DutySwap;
use App\Services\DutySwapService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DutySwapController extends Controller
{
    public function __construct(private DutySwapService $swaps) {}

    public function store(Request $request, DutyAssignment $assignment): RedirectResponse
    {
        Gate::authorize('offerSwap', $assignment);

        return $this->run(
            fn () => $this->swaps->offer($assignment, $request->user()),
            'Your swap is posted. Your admin will confirm once someone claims it.',
        );
    }

    public function claim(Request $request, DutySwap $swap): RedirectResponse
    {
        Gate::authorize('claim', $swap);

        return $this->run(
            fn () => $this->swaps->claim($swap, $request->user()),
            'Thanks for stepping in! Waiting for your admin to approve.',
        );
    }

    public function cancel(Request $request, DutySwap $swap): RedirectResponse
    {
        Gate::authorize('cancel', $swap);

        return $this->run(
            fn () => $this->swaps->cancel($swap, $request->user()),
            "Swap cancelled. You're still on this duty.",
        );
    }

    private function run(Closure $action, string $message): RedirectResponse
    {
        try {
            $action();
        } catch (RosterRuleViolation $e) {
            return redirect()->route('dashboard')->with('error', $e->getMessage());
        }

        return redirect()->route('dashboard')->with('status', $message);
    }
}
