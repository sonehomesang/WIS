<?php

use App\Models\MaintenanceTemplate;
use Database\Seeders\MaintenanceTemplateSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Turn the seeded forklift PM template into a category master:
 * rename it, drop its link to a specific vehicle (equipment_id → null), and
 * scope it to the "Vehicles" category so it shows for every forklift.
 * Idempotent + only touches the seeded record (matched by its old name).
 */
return new class extends Migration
{
    public function up(): void
    {
        MaintenanceTemplate::withTrashed()
            ->where('name', MaintenanceTemplateSeeder::OLD_NAME)
            ->get()
            ->each(function (MaintenanceTemplate $t) {
                $t->forceFill([
                    'name' => MaintenanceTemplateSeeder::NAME,
                    'equipment_id' => null,
                    'category' => MaintenanceTemplateSeeder::CATEGORY,
                ])->saveQuietly();
            });
    }

    public function down(): void
    {
        // one-way rename of seeded data — nothing to roll back.
    }
};
