<?php

namespace App\Http\Controllers;

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
            'interests' => JoinApplication::INTERESTS,
        ]);
    }

    public function store(StoreJoinApplicationRequest $request): RedirectResponse
    {
        // The honeypot is handled in the form request, before validation.
        // The lock stops two simultaneous submits with one email from both passing the duplicate check.
        $email = $request->validated('email');

        $saved = Cache::lock('join-application:'.$email, 10)->block(5, function () use ($request, $email) {
            if (StoreJoinApplicationRequest::emailTaken($email)) {
                return false;
            }

            JoinApplication::create([
                ...$request->safe()->only([
                    'full_name', 'age', 'contact_number', 'email', 'facebook_url',
                    'branch_id', 'interests', 'experience', 'availability',
                ]),
                'consented_at' => now(),
            ]);

            return true;
        });

        if (! $saved) {
            throw ValidationException::withMessages(['email' => StoreJoinApplicationRequest::DUPLICATE_MESSAGE]);
        }

        return redirect()->route('join.thanks');
    }

    public function thanks(): View
    {
        return view('join.thanks');
    }
}
