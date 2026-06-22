<?php

namespace App\Console\Commands;

use App\Models\Material;
use App\Models\Supplier;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Import WIS materials (catalog) + their suppliers from emulator-snapshot JSON.
 *
 *   php artisan material:import-json {materials.json} --suppliers=suppliers.json [--fresh]
 *
 * JSON shape: array of { id, data: { category, description, unit, unitPrice, currency,
 *   supplierId, isActive, ... } }. Idempotent via materials.wis_id.
 */
class ImportMaterialsJson extends Command
{
    protected $signature = 'material:import-json {path} {--suppliers= : suppliers snapshot json} {--fresh : ລຶບ migrated materials ກ່ອນ}';

    protected $description = 'Import materials catalog (+ suppliers) ຈาก WIS emulator-snapshot JSON';

    public function handle(): int
    {
        $path = $this->argument('path');
        if (! is_file($path)) {
            $this->error("ບໍ່ພົບ: {$path}");

            return self::FAILURE;
        }
        $rows = json_decode(file_get_contents($path), true);
        if (! is_array($rows)) {
            $this->error('JSON ບໍ່ຖືກຮູບແບບ');

            return self::FAILURE;
        }

        $suppliers = [];
        if ($sp = $this->option('suppliers')) {
            if (is_file($sp)) {
                foreach (json_decode(file_get_contents($sp), true) ?: [] as $s) {
                    $sid = $s['id'] ?? ($s['data']['id'] ?? null);
                    if ($sid) {
                        $suppliers[$sid] = $s['data'] ?? $s;
                    }
                }
            }
        }

        if ($this->option('fresh')) {
            Material::query()->whereNotNull('wis_id')->forceDelete();
            $this->warn('ລຶບ migrated materials (wis_id) ເກົ່າແລ້ວ');
        }

        $supplierMap = [];   // wis supplierId => WH supplier id
        $created = 0;
        $updated = 0;

        foreach ($rows as $row) {
            $d = $row['data'] ?? $row;
            $wisId = $row['id'] ?? ($d['id'] ?? null);
            if (! $wisId) {
                continue;
            }

            $supplierId = $this->resolveSupplier($d['supplierId'] ?? null, $suppliers, $supplierMap);

            $attrs = [
                'supplier_id' => $supplierId,
                'category' => $this->str($d['category'] ?? 'Uncategorized', 128) ?: 'Uncategorized',
                'description' => (string) ($d['description'] ?? '—'),
                'unit' => $this->str($d['unit'] ?? null, 32),
                'unit_price' => isset($d['unitPrice']) && $d['unitPrice'] !== '' ? (float) $d['unitPrice'] : null,
                'currency' => in_array($d['currency'] ?? null, ['LAK', 'THB', 'USD'], true) ? $d['currency'] : 'THB',
                'is_active' => (bool) ($d['isActive'] ?? true),
                'last_price_update' => isset($d['unitPrice']) ? Carbon::today()->toDateString() : null,
            ];

            $existing = Material::where('wis_id', $wisId)->first();
            if ($existing) {
                $existing->fill($attrs)->save();
                $updated++;
            } else {
                Material::create($attrs + ['wis_id' => $wisId]);
                $created++;
            }
        }

        $this->info("✓ materials: created {$created}, updated {$updated} · suppliers mapped: ".count($supplierMap));

        return self::SUCCESS;
    }

    /** find-or-create WH supplier ຈาก WIS supplierId (ໃຊ້ wis_id ກ່ອນ, ບໍ່ມີ → name). */
    private function resolveSupplier(?string $wisSupplierId, array $suppliers, array &$map): ?int
    {
        if (! $wisSupplierId) {
            return null;
        }
        if (isset($map[$wisSupplierId])) {
            return $map[$wisSupplierId];
        }

        $data = $suppliers[$wisSupplierId] ?? null;
        $name = $data['name'] ?? $wisSupplierId;

        $supplier = Supplier::where('wis_id', $wisSupplierId)->first()
            ?? Supplier::whereNull('wis_id')->where('name', $name)->first();

        if (! $supplier) {
            $supplier = Supplier::create([
                'wis_id' => $wisSupplierId,
                'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
                'name' => $name,
                'name_en' => $data['nameEn'] ?? null,
                'address' => $data['address'] ?? null,
                'contact_person' => $data['contactPerson'] ?? null,
                'contact_phone' => $data['contactPhone'] ?? null,
                'contact_email' => $data['contactEmail'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_active' => (bool) ($data['isActive'] ?? true),
            ]);
        } elseif (! $supplier->wis_id) {
            $supplier->update(['wis_id' => $wisSupplierId]);
        }

        return $map[$wisSupplierId] = $supplier->id;
    }

    private function str(?string $v, int $max): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }

        return Str::limit(trim($v), $max, '');
    }
}
