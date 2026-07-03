<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\EquipmentCategory;
use Illuminate\Database\Seeder;

class EquipmentCategorySeeder extends Seeder
{
    public function run(): void
    {
        // ຄ່າ ຕັ້ງຕົ້ນ ທົ່ວໄປ + ຮວມ ປະເພດ ທີ່ ມີ ຢູ່ ແລ້ວ ໃນ ຂໍ້ມູນ (trim ຊ່ອງ ວ່າງ).
        $defaults = ['Generator', 'Vehicle', 'Forklift', 'Power tool', 'Hand tool', 'Sling', 'Measuring instrument', 'Safety equipment', 'Other'];

        $existing = Equipment::whereNotNull('category')->distinct()->pluck('category')
            ->map(fn ($c) => trim((string) $c))->filter()->all();

        $names = collect($defaults)->merge($existing)->unique()->values();

        foreach ($names as $i => $name) {
            EquipmentCategory::firstOrCreate(['name' => $name], ['is_active' => true, 'sort_order' => $i]);
        }
    }
}
