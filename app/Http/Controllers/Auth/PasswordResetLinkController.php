<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Same answer for known and unknown emails, so this form cannot be used
        // to find out who has an account.
        // The broker work (user lookup, token, throttle check) runs after the response is sent,
        // so response time and content never depend on whether the email exists or is throttled.
        $credentials = $request->only('email');
        app()->terminating(fn () => Password::sendResetLink($credentials));

        return back()->with('status', __('If that email is registered, we sent a link to reset the password.'));
    }
}
