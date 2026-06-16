<?php

namespace App\Providers;

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
        // super_admin ມີສິດເໜືອທຸກຢ່າງ (ເໜືອ admin ທົ່ວໄປ) — bypass ທຸກ permission check.
        Gate::before(fn ($user, $ability) => $user->is_super_admin ? true : null);
    }
}
