<?php

use App\Models\MaintenanceTemplate;
use Database\Seeders\MaintenanceTemplateSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfill the per-item "applies" (both|ev|engine) onto maintenance_templates
 * that predate fuel-type filtering. Matches each stored item to the seeder
 * definition by (label|remark); unmatched items fall back to "both" (shown for
 * every vehicle type — safe default). Mirrors the group backfill.
 */
return new class extends Migration
{
    public function up(): void
    {
        $lookup = [];
        foreach (MaintenanceTemplateSeeder::items() as $it) {
            $key = trim($it['label']).'|||'.trim($it['remark'] ?? '');
            $lookup[$key] = $it['applies'];
        }

        MaintenanceTemplate::withTrashed()->get()->each(function (MaintenanceTemplate $t) use ($lookup) {
            $items = $t->items;
            if (! is_array($items) || $items === []) {
                return;
            }
            $changed = false;
            foreach ($items as &$it) {
                if (! is_array($it) || (isset($it['applies']) && $it['applies'] !== '')) {
                    continue;
                }
                $key = trim((string) ($it['label'] ?? '')).'|||'.trim((string) ($it['remark'] ?? ''));
                $it['applies'] = $lookup[$key] ?? 'both';
                $changed = true;
            }
            unset($it);
            if ($changed) {
                $t->items = $items;
                $t->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        // additive metadata on a JSON column — nothing to roll back.
    }
};
