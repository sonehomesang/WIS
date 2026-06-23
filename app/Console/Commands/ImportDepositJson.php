<?php

namespace App\Console\Commands;

use App\Models\DepositRecord;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Import WIS deposit records from emulator-snapshot JSON.
 *
 *   php artisan deposit:import-json {deposits.json} --photos=storage/depositRecords [--by=<userId>]
 *
 * Idempotent via request_number. Photos under {dir}/{wis_id}/items/{idx}/{kind}_*.jpg.
 */
class ImportDepositJson extends Command
{
    protected $signature = 'deposit:import-json {path} {--photos=} {--by= : fallback owner user id} {--fresh}';

    protected $description = 'Import deposit records (+items, +photos) from WIS snapshot';

    public function handle(): int
    {
        $path = $this->argument('path');
        if (! is_file($path)) {
            $this->error("ບໍ່ພົບ: {$path}");

            return self::FAILURE;
        }
        $rows = json_decode(file_get_contents($path), true) ?: [];

        if ($this->option('fresh')) {
            DepositRecord::query()->forceDelete();
            $this->warn('ລຶບ deposits ເກົ່າແລ້ວ');
        }

        $fallback = $this->option('by')
            ? (int) $this->option('by')
            : optional(User::where('is_super_admin', true)->first())->id;

        $photoDir = $this->option('photos');
        $created = 0;
        $updated = 0;
        $photos = 0;

        foreach ($rows as $row) {
            $d = $row['data'] ?? $row;
            $wisId = $row['id'] ?? ($d['id'] ?? null);
            $num = $d['requestNumber'] ?? null;
            if (! $num) {
                continue;
            }

            $owner = ! empty($d['ownerEmail']) ? User::where('email', mb_strtolower($d['ownerEmail']))->first() : null;
            $storage = collect([$d['storageLocationId'] ?? null, $d['storageBuildingId'] ?? null, $d['storageRoomId'] ?? null])->filter()->implode(' / ');

            $attrs = [
                'owner_user_id' => $owner?->id ?? $fallback,
                'owner_email' => mb_strtolower($d['ownerEmail'] ?? ''),
                'owner_name' => $d['ownerName'] ?? null,
                'request_type' => in_array($d['requestType'] ?? null, ['walk_in', 'pre_request'], true) ? $d['requestType'] : 'walk_in',
                'item_category' => $d['itemCategory'] ?? null,
                'origin_source' => $d['originSource'] ?? null,
                'deposit_reason' => $d['depositReason'] ?? null,
                'expected_duration' => $d['expectedDuration'] ?? null,
                'deposit_date' => $this->date($d['depositDate'] ?? null) ?? Carbon::today()->toDateString(),
                'expected_claim_date' => $this->date($d['expectedClaimDate'] ?? null),
                'actual_claim_date' => $this->date($d['actualClaimDate'] ?? null),
                'remark' => $d['remark'] ?? null,
                'storage_location' => $storage ?: null,
                'storage_shelf_label' => $d['storageShelfLabel'] ?? null,
                'warehouse_instructions' => $d['warehouseInstructions'] ?? null,
                'warehouse_staff_name' => $d['warehouseStaffName'] ?? null,
                'accepted_at' => $this->ts($d['acceptedAt'] ?? null),
                'stored_at' => $this->ts($d['storedAt'] ?? null),
                'needs_fix_reason' => $d['needsFixReason'] ?? null,
                'needs_fix_flagged_at' => $this->ts($d['needsFixFlaggedAt'] ?? null),
                'claimed_at' => $this->ts($d['claimedAt'] ?? null),
                'cancel_reason' => $d['cancelReason'] ?? null,
                'cancelled_at' => $this->ts($d['cancelledAt'] ?? null),
                'status' => $d['status'] ?? 'draft',
            ];

            $existing = DepositRecord::withTrashed()->where('request_number', $num)->first();
            if ($existing) {
                $existing->fill($attrs)->save();
                $rec = $existing;
                $updated++;
            } else {
                $rec = DepositRecord::create($attrs + ['request_number' => $num]);
                foreach (array_values($d['items'] ?? []) as $i => $it) {
                    $item = $rec->items()->create([
                        'item_name' => $it['itemName'] ?? '—',
                        'description' => $it['description'] ?? null,
                        'qty' => max(1, (int) ($it['qty'] ?? 1)),
                        'unit' => $it['unit'] ?? null,
                        'condition_on_deposit' => $it['conditionOnDeposit'] ?? null,
                        'condition_on_claim' => $it['conditionOnClaim'] ?? null,
                        'sort_order' => $i,
                    ]);
                    if ($photoDir && $wisId) {
                        $photos += $this->importItemPhotos($item, rtrim($photoDir, '/\\')."/{$wisId}/items/{$i}");
                    }
                }
                $created++;
            }
        }

        $this->info("✓ deposits: created {$created}, updated {$updated} · photos {$photos}");

        return self::SUCCESS;
    }

    private function importItemPhotos($item, string $dir): int
    {
        if (! is_dir($dir)) {
            return 0;
        }
        $n = 0;
        foreach (glob($dir.'/*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE) as $file) {
            $base = strtolower(basename($file));
            $kind = Str::startsWith($base, 'stored') ? 'stored' : (Str::startsWith($base, 'claim') ? 'claim' : 'deposit');
            $target = "deposit/{$item->record_id}/{$item->id}/".basename($file);
            Storage::disk('public')->put($target, file_get_contents($file));
            $item->photos()->create(['kind' => $kind, 'path' => $target, 'sort_order' => $n]);
            $n++;
        }

        return $n;
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
