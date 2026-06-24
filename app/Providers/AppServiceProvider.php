<?php

namespace App\Providers;

use App\Models\Material;
use App\Observers\MaterialObserver;
use Illuminate\Support\Facades\Blade;
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

        Material::observe(MaterialObserver::class);

        // @term('key', 'default fallback') — admin-overridable wording.
        Blade::directive('term', function (string $expr) {
            return "<?php echo e(\\App\\Models\\Translation::term({$expr})); ?>";
        });
    }
}
