<?php

use App\Models\MaintenanceTemplate;
use Database\Seeders\MaintenanceTemplateSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Retry of the forklift master rename. The exact-name match in the previous
 * migration missed servers whose seeded record used a slightly different dash or
 * spacing, so match by the distinctive "TCM FD30T3Z" prefix instead (LIKE).
 * No-op where the rename already applied (name is now VC-FLPMCM…).
 */
return new class extends Migration
{
    public function up(): void
    {
        MaintenanceTemplate::withTrashed()
            ->where('name', 'like', 'TCM FD30T3Z%')
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
