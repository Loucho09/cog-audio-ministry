<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Users given a temporary password must set their own before doing anything else.
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

        // Livewire/JSON callers cannot follow a page redirect; refuse them outright.
        if ($request->expectsJson() || $request->hasHeader('X-Livewire')) {
            abort(403, 'You must change your password first.');
        }

        return redirect()->route('password.change');
    }
}
