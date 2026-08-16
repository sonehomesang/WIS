<?php

namespace Database\Seeders;

use App\Models\ConditionStatus;
use App\Support\ConditionStatus as Catalog;
use Illuminate\Database\Seeder;

class ConditionStatusSeeder extends Seeder
{
    public function run(): void
    {
        // Seed-once: create missing defaults, never overwrite an admin's edits.
        foreach (Catalog::DEFAULTS as $i => $d) {
            ConditionStatus::firstOrCreate(
                ['key' => $d['key']],
                [
                    'label' => $d['label'],
                    'color' => $d['color'],
                    'is_disposable' => $d['is_disposable'],
                    'is_active' => true,
                    'sort_order' => $i,
                ]
            );
        }

        Catalog::forget();
    }
}
