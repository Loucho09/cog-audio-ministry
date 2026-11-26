<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpdateNotificationPreferencesRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->safe()->only(['name', 'email']));

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Turn duty emails on or off. In-app notifications are always kept.
     */
    public function updateNotifications(UpdateNotificationPreferencesRequest $request): RedirectResponse
    {
        // forceFill: the column is deliberately not mass-assignable.
        $request->user()->forceFill([
            'email_notifications' => $request->boolean('email_notifications'),
        ])->save();

        return Redirect::route('profile.edit')->with('status', 'notifications-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Only plain members (or role-less accounts) may delete themselves.
        abort_unless($user->canSelfDelete(), 403);

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        // Duty history is kept (restrict foreign key), so the account stays.
        if ($user->dutyAssignments()->exists()) {
            return back()->withErrors([
                'password' => "You have duty history, so your account can't be deleted here. Please contact your branch leader.",
            ], 'userDeletion');
        }

        // Log out first: logout() saves the user, which would re-insert a deleted model.
        Auth::logout();

        // Model delete (not a query) so Spatie detaches the role rows.
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
