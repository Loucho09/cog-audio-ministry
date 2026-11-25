<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
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
        Password::defaults(fn () => Password::min(10)->letters()->numbers());

        RateLimiter::for('password-change', fn (Request $request) => Limit::perMinutes(10, 5)->by('password-change:'.$request->user()?->id));

        // Per email+IP so people sharing one church IP do not lock each other out,
        // plus a looser IP-only ceiling against floods.
        RateLimiter::for('auth-attempts', fn (Request $request) => [
            Limit::perMinute(5)->by('email:'.Str::transliterate(Str::lower($request->string('email')->toString())).'|'.$request->ip()),
            Limit::perMinute(30)->by('ip:'.$request->ip()),
        ]);

        // Public join form: a few submits per minute per IP.
        RateLimiter::for('join-form', fn (Request $request) => Limit::perMinute(5)->by('join:'.$request->ip()));

        // Gate::before runs ahead of every Policy: super-admin is allowed everything.
        // Returning null for everyone else lets their Policy decide.
        Gate::before(fn (User $user) => $user->hasRole('super-admin') ? true : null);
    }
}
