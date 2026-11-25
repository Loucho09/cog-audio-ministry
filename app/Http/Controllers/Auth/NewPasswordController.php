<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\InviteToken;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        // Same check as store(): only a validly signed marker makes this a welcome invite.
        $rawInviteToken = InviteToken::unwrap((string) $request->route('token'));
        $isInvite = $rawInviteToken !== null;

        // Greet by name only when the invite token really belongs to this email. The signed marker covers the
        // token, not the email, so without this check anyone holding one invite link could swap in another
        // email and read that person's name. The normal reset page never looks anyone up.
        $firstName = null;

        if ($isInvite && filled($request->query('email'))) {
            $user = User::query()->where('email', (string) $request->query('email'))->first();

            if ($user && Password::broker('invites')->tokenExists($user, $rawInviteToken)) {
                $firstName = Str::before(trim($user->name), ' ') ?: null;
            }
        }

        return view('auth.reset-password', [
            'request' => $request,
            'isInvite' => $isInvite,
            'firstName' => $firstName,
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        // Welcome links carry a signed marker and are checked by the 3-day 'invites'
        // broker; everything else uses the default 60-minute broker.
        $credentials = $request->only('email', 'password', 'password_confirmation', 'token');
        $rawToken = InviteToken::unwrap((string) $request->input('token'));

        if ($rawToken !== null) {
            $credentials['token'] = $rawToken;
        }

        $status = Password::broker($rawToken !== null ? 'invites' : null)->reset(
            $credentials,
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                    'must_change_password' => false,
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($status == Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', $rawToken !== null
                ? __('Your password is set. Log in with your email and new password.')
                : __('Your password has been reset. Log in with your new password.'));
        }

        $message = $status == Password::INVALID_TOKEN
            ? __('This link has expired or was replaced by a newer one. Please request a new link, or ask your branch head.')
            : __($status);

        return back()->withInput($request->only('email'))->withErrors(['email' => $message]);
    }
}
