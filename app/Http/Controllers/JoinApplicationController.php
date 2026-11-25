<?php

namespace App\Http\Controllers;

use App\Actions\NotifyStaffOfJoinApplication;
use App\Http\Requests\StoreJoinApplicationRequest;
use App\Models\Branch;
use App\Models\JoinApplication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class JoinApplicationController extends Controller
{
    public function create(): View
    {
        return view('join.create', [
            'branches' => Branch::orderByDesc('is_main')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreJoinApplicationRequest $request, NotifyStaffOfJoinApplication $notifyStaff): RedirectResponse
    {
        // The honeypot is handled in the form request, before validation.
        // The lock stops two simultaneous submits with one email from both passing the duplicate check.
        $email = $request->validated('email');

        $saved = Cache::lock('join-application:'.$email, 10)->block(5, function () use ($request, $email) {
            if (StoreJoinApplicationRequest::emailTaken($email)) {
                return false;
            }

            return JoinApplication::create([
                ...$request->safe()->only([
                    'full_name', 'age', 'contact_number', 'email', 'facebook_url',
                    'branch_id',
                ]),
                'consented_at' => now(),
            ]);
        });

        if (! $saved) {
            throw ValidationException::withMessages(['email' => StoreJoinApplicationRequest::DUPLICATE_MESSAGE]);
        }

        // Dispatched after the lock is released; failures are logged, never shown to the applicant.
        $notifyStaff->handle($saved);

        return redirect()->route('join.thanks');
    }

    public function thanks(): View
    {
        return view('join.thanks');
    }
}
