<?php

namespace App\Providers;

use App\Models\Material;
use App\Observers\MaterialObserver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
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
        // super_admin ມີສິດເໜືອທຸກຢ່າງ (ເໜືອ admin ທົ່ວໄປ) — bypass ທຸກ permission check.
        Gate::before(fn ($user, $ability) => $user->is_super_admin ? true : null);

        // ນະໂຍບາຍ ລະຫັດຜ່ານ ຂັ້ນຕ່ຳ (ISO-aligned): ຍາວ ≥ 10. ໃຊ້ ກັບ ໜ້າ ຕັ້ງ/reset ລະຫັດ.
        // ບໍ່ ບັງຄັບ symbol/case — ຄວາມ ຍາວ ໃຫ້ ປະໂຫຍກ ສັ້ນໆ ຜ່ານ ໄດ້ (ບໍ່ ລຳຄານ).
        Password::defaults(fn () => Password::min(10));

        Material::observe(MaterialObserver::class);

        // @term('key', 'default fallback') — admin-overridable wording.
        Blade::directive('term', function (string $expr) {
            return "<?php echo e(\\App\\Models\\Translation::term({$expr})); ?>";
        });
    }
}
