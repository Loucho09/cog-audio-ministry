<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Users given a temporary password are nagged in the member area and blocked from the admin panel
 * until they set their own.
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs('password.change', 'password.change.store', 'logout', 'filament.*.auth.logout')) {
            return $next($request);
        }

        $isLivewireOrJson = $request->expectsJson()
            || $request->hasHeader('X-Livewire')
            || $request->is('livewire*', 'livewire*/*');

        // Changing email or deleting the account would bypass the forced change (email + reset).
        if ($request->routeIs('profile.update', 'profile.destroy')) {
            if ($isLivewireOrJson) {
                abort(403, 'You must change your password first.');
            }

            return redirect()->route('password.change');
        }

        // Member-area pages stay usable (a banner nags instead); only the admin panel,
        // Livewire updates and JSON calls are blocked until the password is changed.
        if (! $isLivewireOrJson && ! $request->routeIs('filament.*')) {
            return $next($request);
        }

        // Livewire/JSON callers cannot follow a page redirect; refuse them outright.
        if ($isLivewireOrJson) {
            abort(403, 'You must change your password first.');
        }

        return redirect()->route('password.change');
    }
}
