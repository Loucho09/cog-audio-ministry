<?php

namespace App\Http\Controllers;

use App\Enums\DutyStatus;
use App\Exceptions\RosterRuleViolation;
use App\Models\DutyAssignment;
use App\Services\DutyRosterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DutyResponseController extends Controller
{
    public function __construct(private DutyRosterService $roster) {}

    public function accept(Request $request, DutyAssignment $assignment): RedirectResponse
    {
        return $this->answer($request, $assignment, DutyStatus::Accepted, 'Thanks! You accepted this duty.');
    }

    public function decline(Request $request, DutyAssignment $assignment): RedirectResponse
    {
        return $this->answer($request, $assignment, DutyStatus::Declined, 'You declined this duty.');
    }

    private function answer(Request $request, DutyAssignment $assignment, DutyStatus $answer, string $message): RedirectResponse
    {
        Gate::authorize('respond', $assignment);

        try {
            $this->roster->respond($assignment, $request->user(), $answer);
        } catch (RosterRuleViolation $e) {
            return redirect()->route('dashboard')->with('error', $e->getMessage());
        }

        return redirect()->route('dashboard')->with('status', $message);
    }
}
