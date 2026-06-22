<?php

namespace App\Console\Commands;

use App\Models\DiscrepancyAdvice;
use App\Models\OutwardsGoodsAdvice;
use App\Models\Supplier;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Import WIS Outwards Goods Advices (OGA) from emulator-snapshot JSON.
 *
 *   php artisan oga:import-json {oga.json} --suppliers=suppliers.json --photos=storage/outwardsGoodsAdvices
 *
 * Idempotent via oga_number. Photo kind from filename prefix.
 */
class ImportOgaJson extends Command
{
    protected $signature = 'oga:import-json {path} {--suppliers=} {--photos=} {--fresh}';

    protected $description = 'Import Outwards Goods Advices (+suppliers, +photos) from WIS snapshot';

    public function handle(): int
    {
        $path = $this->argument('path');
        if (! is_file($path)) {
            $this->error("ບໍ່ພົບ: {$path}");

            return self::FAILURE;
        }
        $rows = json_decode(file_get_contents($path), true) ?: [];

        $suppliers = [];
        if (($sp = $this->option('suppliers')) && is_file($sp)) {
            foreach (json_decode(file_get_contents($sp), true) ?: [] as $s) {
                $sid = $s['id'] ?? ($s['data']['id'] ?? null);
                if ($sid) {
                    $suppliers[$sid] = $s['data'] ?? $s;
                }
            }
        }

        if ($this->option('fresh')) {
            OutwardsGoodsAdvice::query()->forceDelete();
            $this->warn('ລຶບ OGA ເກົ່າແລ້ວ');
        }

        $photoDir = $this->option('photos');
        $created = 0;
        $updated = 0;
        $photos = 0;
        $map = [];

        foreach ($rows as $row) {
            $d = $row['data'] ?? $row;
            $wisId = $row['id'] ?? ($d['id'] ?? null);
            $num = $d['ogaNumber'] ?? null;
            if (! $num) {
                continue;
            }

            $sourceDa = ! empty($d['sourceDaNumber']) ? DiscrepancyAdvice::where('da_number', $d['sourceDaNumber'])->first() : null;
            $attrs = [
                'date' => $this->date($d['date'] ?? null) ?? Carbon::today()->toDateString(),
                'po_number' => $d['poNumber'] ?? null,
                'ship_via' => $d['shipVia'] ?? null,
                'source_type' => $d['sourceType'] ?? (! empty($d['sourceDaNumber']) ? 'da' : 'oga'),
                'source_da_id' => $sourceDa?->id,
                'source_da_number' => $d['sourceDaNumber'] ?? null,
                'supplier_id' => $this->resolveSupplier($d['supplierId'] ?? null, $suppliers, $map),
                'dispatch_to_name' => $d['dispatchToName'] ?? null,
                'dispatch_to_address' => $d['dispatchToAddress'] ?? null,
                'dispatch_to_phone' => $d['dispatchToPhone'] ?? null,
                'dispatch_to_contact_person' => $d['dispatchToContactPerson'] ?? null,
                'dispatch_to_email' => $d['dispatchToEmail'] ?? null,
                'goods_consigned' => $d['goodsConsigned'] ?? null,
                'dimension' => $d['dimension'] ?? null,
                'gross_weight_kg' => $d['grossWeightKg'] ?? null,
                'total_weight_kg' => $d['totalWeightKg'] ?? null,
                'reason_of_despatch' => $d['reasonOfDespatch'] ?? null,
                'comments' => $d['comments'] ?? null,
                'driver_name' => $d['driverName'] ?? null,
                'truck_plate_number' => $d['truckPlateNumber'] ?? null,
                'consign_by_name' => $d['consignByName'] ?? null,
                'authorized_by_name' => $d['authorizedByName'] ?? null,
                'authorized_at' => $this->ts($d['authorizedAt'] ?? null),
                'completed_by_name' => $d['completedByName'] ?? null,
                'completed_at' => $this->ts($d['completedAt'] ?? null),
                'status' => $d['status'] ?? 'draft',
                'reject_reason' => $d['rejectReason'] ?? null,
                'cancel_reason' => $d['cancelReason'] ?? null,
            ];

            $existing = OutwardsGoodsAdvice::withTrashed()->where('oga_number', $num)->first();
            if ($existing) {
                $existing->fill($attrs)->save();
                $oga = $existing;
                $updated++;
            } else {
                $oga = OutwardsGoodsAdvice::create($attrs + ['oga_number' => $num]);
                foreach (array_values($d['items'] ?? []) as $i => $it) {
                    $oga->items()->create([
                        'description' => $it['description'] ?? '—',
                        'unit' => $it['unit'] ?? null,
                        'qty' => (int) ($it['qty'] ?? 1),
                        'unit_weight_kg' => $it['unitWeightKg'] ?? null,
                        'total_weight_kg' => $it['totalWeightKg'] ?? null,
                        'sort_order' => $i,
                    ]);
                }
                $created++;
            }

            if ($photoDir && $wisId && ! $oga->photos()->exists()) {
                $base = rtrim($photoDir, '/\\')."/{$wisId}";
                $photos += $this->importPhotos($oga, $base.'/photos');
                $photos += $this->importPhotos($oga, $base.'/delivery-photos');
            }
        }

        $this->info("✓ OGA: created {$created}, updated {$updated} · photos {$photos} · suppliers mapped ".count($map));

        return self::SUCCESS;
    }

    private function importPhotos(OutwardsGoodsAdvice $oga, string $dir): int
    {
        if (! is_dir($dir)) {
            return 0;
        }
        $n = 0;
        foreach (glob($dir.'/*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE) as $file) {
            $base = strtolower(basename($file));
            $kind = match (true) {
                Str::startsWith($base, 'sealed') => 'sealed',
                Str::startsWith($base, 'paper') => 'paper_pli',
                Str::startsWith($base, 'delivered') => 'delivered',
                Str::startsWith($base, 'handover') => 'handover',
                Str::startsWith($base, 'receipt') => 'receipt',
                default => 'loaded',
            };
            $target = "oga/{$oga->id}/".basename($file);
            Storage::disk('public')->put($target, file_get_contents($file));
            $oga->photos()->create(['kind' => $kind, 'path' => $target, 'sort_order' => $n]);
            $n++;
        }

        return $n;
    }

    private function resolveSupplier(?string $wisId, array $suppliers, array &$map): ?int
    {
        if (! $wisId) {
            return null;
        }
        if (isset($map[$wisId])) {
            return $map[$wisId];
        }
        $data = $suppliers[$wisId] ?? null;
        $name = $data['name'] ?? $wisId;
        $s = Supplier::where('wis_id', $wisId)->first() ?? Supplier::whereNull('wis_id')->where('name', $name)->first();
        if (! $s) {
            $s = Supplier::create([
                'wis_id' => $wisId, 'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)), 'name' => $name,
                'name_en' => $data['nameEn'] ?? null, 'address' => $data['address'] ?? null,
                'contact_person' => $data['contactPerson'] ?? null, 'contact_phone' => $data['contactPhone'] ?? null,
                'contact_email' => $data['contactEmail'] ?? null, 'notes' => $data['notes'] ?? null, 'is_active' => true,
            ]);
        } elseif (! $s->wis_id) {
            $s->update(['wis_id' => $wisId]);
        }

        return $map[$wisId] = $s->id;
    }

    private function date(?string $v): ?string
    {
        return $v ? Carbon::parse($v)->toDateString() : null;
    }

    private function ts($v): ?string
    {
        if (is_array($v) && isset($v['seconds'])) {
            return Carbon::createFromTimestamp((int) $v['seconds'])->toDateTimeString();
        }

        return null;
    }
}
