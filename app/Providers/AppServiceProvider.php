<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Gate::before(fn (User $user) => $user->hasRole('super-admin') ? true : null);
    }
}
