<?php

namespace App\Console\Commands;

use App\Models\DiscrepancyAdvice;
use App\Models\Supplier;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Import WIS Discrepancy Advices (DA) from emulator-snapshot JSON.
 *
 *   php artisan da:import-json {da.json} --suppliers=suppliers.json --photos=storage/discrepancyAdvices
 *
 * Idempotent via da_number. Photos: {dir}/{wis_id}/photos/{file}; kind from filename prefix.
 */
class ImportDaJson extends Command
{
    protected $signature = 'da:import-json {path} {--suppliers=} {--photos=} {--fresh}';

    protected $description = 'Import Discrepancy Advices (+suppliers, +photos) from WIS snapshot';

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
            DiscrepancyAdvice::query()->forceDelete();
            $this->warn('ລຶບ DA ເກົ່າແລ້ວ');
        }

        $photoDir = $this->option('photos');
        $created = 0;
        $updated = 0;
        $photos = 0;
        $map = [];

        foreach ($rows as $row) {
            $d = $row['data'] ?? $row;
            $wisId = $row['id'] ?? ($d['id'] ?? null);
            $num = $d['daNumber'] ?? null;
            if (! $num) {
                continue;
            }

            $rec = $d['warehouseRecommendation'] ?? [];
            $tr = $d['returnTransport'] ?? [];
            $attrs = [
                'date' => $this->date($d['date'] ?? null) ?? Carbon::today()->toDateString(),
                'po_number' => $d['poNumber'] ?? null,
                'po_date' => $this->date($d['poDate'] ?? null),
                'supplier_id' => $this->resolveSupplier($d['supplierId'] ?? null, $suppliers, $map),
                'supplier_name' => $d['supplierName'] ?? null,
                'purchasing_officer_name' => $d['purchasingOfficerName'] ?? null,
                'vendor_packing_list' => $this->clean($d['vendorPackingList'] ?? null),
                'vendor_invoice_number' => $this->clean($d['vendorInvoiceNumber'] ?? null),
                'discrepancy_types' => $d['discrepancyTypes'] ?? [],
                'comments' => $d['comments'] ?? null,
                'warehouse_recommendation_kind' => $rec['kind'] ?? null,
                'warehouse_recommendation_text' => $rec['otherText'] ?? null,
                'raised_by_name' => $d['raisedByName'] ?? null,
                'raised_at' => $this->ts($d['raisedAt'] ?? null),
                'purchasing_decisions' => $d['purchasingDecisions'] ?? null,
                'purchasing_note' => $d['purchasingNote'] ?? null,
                'return_transport_account' => $tr['account'] ?? null,
                'return_transport_mode' => $tr['mode'] ?? null,
                'return_carrier_name' => $tr['carrierName'] ?? null,
                'return_carrier_phone' => $tr['carrierPhone'] ?? null,
                'purchasing_decided_at' => $this->ts($d['purchasingDecidedAt'] ?? null),
                'resolution_action' => $d['resolutionAction'] ?? null,
                'approved_by_name' => $d['approvedByName'] ?? null,
                'approved_title' => $d['approvedTitle'] ?? null,
                'approved_at' => $this->ts($d['approvedAt'] ?? null),
                'next_step' => $d['nextStep'] ?? null,
                'status' => $d['status'] ?? 'draft',
                'reject_reason' => $d['rejectReason'] ?? null,
                'cancel_reason' => $d['cancelReason'] ?? null,
            ];

            $existing = DiscrepancyAdvice::withTrashed()->where('da_number', $num)->first();
            if ($existing) {
                $existing->fill($attrs)->save();
                $da = $existing;
                $updated++;
            } else {
                $da = DiscrepancyAdvice::create($attrs + ['da_number' => $num]);
                foreach (array_values($d['items'] ?? []) as $i => $it) {
                    $da->items()->create([
                        'stock_code' => $it['stockCode'] ?? null,
                        'description' => $it['description'] ?? '—',
                        'qty_ordered' => (int) ($it['qtyOrdered'] ?? 0),
                        'qty_delivered' => (int) ($it['qtyDelivered'] ?? 0),
                        'qty_received' => (int) ($it['qtyReceived'] ?? 0),
                        'sort_order' => $i,
                    ]);
                }
                $created++;
            }

            if ($photoDir && $wisId && ! $da->photos()->exists()) {
                $photos += $this->importPhotos($da, rtrim($photoDir, '/\\')."/{$wisId}/photos");
            }
        }

        $this->info("✓ DA: created {$created}, updated {$updated} · photos {$photos} · suppliers mapped ".count($map));

        return self::SUCCESS;
    }

    private function importPhotos(DiscrepancyAdvice $da, string $dir): int
    {
        if (! is_dir($dir)) {
            return 0;
        }
        $n = 0;
        foreach (glob($dir.'/*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE) as $file) {
            $base = basename($file);
            $kind = Str::startsWith($base, 'defect') ? 'defect' : (Str::startsWith($base, 'comparison') ? 'comparison' : 'overview');
            $target = "da/{$da->id}/{$base}";
            Storage::disk('public')->put($target, file_get_contents($file));
            $da->photos()->create(['kind' => $kind, 'path' => $target, 'sort_order' => $n]);
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

    private function clean(?string $v): ?string
    {
        return ($v === null || $v === '' || strtoupper(trim($v)) === 'N/A') ? null : $v;
    }
}
