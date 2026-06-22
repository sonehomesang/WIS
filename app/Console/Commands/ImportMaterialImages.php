<?php

namespace App\Console\Commands;

use App\Models\Material;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * material:import-images {dir} — import material catalog images ຈาก WIS Storage export.
 *
 * Source layout: {dir}/{wis_id}/{file.jpg} (dir name = materials.wis_id).
 * Copies into storage/app/public/materials/{wis_id}/{file} + creates material_images rows.
 * Idempotent: skip a material ທີ່ມີຮູບແລ້ວ.
 */
class ImportMaterialImages extends Command
{
    protected $signature = 'material:import-images {dir : source folder, wis_id subfolders with image files}';

    protected $description = 'Import material catalog images from a WIS Storage export folder';

    public function handle(): int
    {
        $dir = rtrim($this->argument('dir'), '/\\');
        if (! is_dir($dir)) {
            $this->error("ບໍ່ພົບ folder: {$dir}");

            return self::FAILURE;
        }

        $materials = Material::whereNotNull('wis_id')->get()->keyBy('wis_id');
        $imported = 0;
        $skipped = 0;
        $missing = 0;

        foreach (glob($dir.'/*', GLOB_ONLYDIR) as $sub) {
            $wisId = basename($sub);
            $m = $materials->get($wisId);
            if (! $m) {
                $missing++;

                continue;
            }
            if ($m->images()->exists()) {
                $skipped++;

                continue;
            }

            $sort = 0;
            foreach (glob($sub.'/*.{jpg,jpeg,png,JPG,JPEG,PNG,webp,WEBP}', GLOB_BRACE) as $file) {
                $name = basename($file);
                $target = "materials/{$wisId}/{$name}";
                Storage::disk('public')->put($target, file_get_contents($file));
                $m->images()->create(['path' => $target, 'sort_order' => $sort++]);
                $imported++;
            }
        }

        $this->info("✓ images imported: {$imported} · materials skipped (ມີຮູບແລ້ວ): {$skipped} · dirs ບໍ່ພົບ material: {$missing}");

        return self::SUCCESS;
    }
}
