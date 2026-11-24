<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Gate::before runs ahead of every Policy: super-admin is allowed everything.
        // Returning null for everyone else lets their Policy decide.
        Password::defaults(fn () => Password::min(10)->letters()->numbers());

        RateLimiter::for('password-change', fn (Request $request) => Limit::perMinutes(10, 5)->by('password-change:'.$request->user()?->id));

        Gate::before(fn (User $user) => $user->hasRole('super-admin') ? true : null);
    }
}
