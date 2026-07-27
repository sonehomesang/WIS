<?php

use App\Models\MaintenanceTemplate;
use Database\Seeders\MaintenanceTemplateSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfill the new per-item "group" onto maintenance_templates that were created
 * before grouping existed. Matches each stored item to the seeder definition by
 * (label|remark) — unique even for the duplicated "suction strainer" label whose
 * remark (part number) differs. Unmatched items fall back to "other".
 *
 * Fresh installs run this on an empty table (no-op) and get grouped items from
 * the seeder instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        $lookup = [];
        foreach (MaintenanceTemplateSeeder::items() as $it) {
            $key = trim($it['label']).'|||'.trim($it['remark'] ?? '');
            $lookup[$key] = $it['group'];
        }

        MaintenanceTemplate::withTrashed()->get()->each(function (MaintenanceTemplate $t) use ($lookup) {
            $items = $t->items;
            if (! is_array($items) || $items === []) {
                return;
            }
            $changed = false;
            foreach ($items as &$it) {
                if (! is_array($it) || (isset($it['group']) && $it['group'] !== '')) {
                    continue;
                }
                $key = trim((string) ($it['label'] ?? '')).'|||'.trim((string) ($it['remark'] ?? ''));
                $it['group'] = $lookup[$key] ?? 'other';
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
        // group is additive metadata on a JSON column — nothing to roll back.
    }
};
