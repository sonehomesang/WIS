<?php

namespace App\Console\Commands;

use App\Models\BorrowRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * borrow:import-photos — import borrow item photos ຈາກ WIS export → WH storage (Phase 6.6.9).
 *
 * Reads borrow_photos_manifest.json (request_number, item_index, kind, file) + the downloaded
 * files, copies into storage/app/public/borrow/{recordId}/{itemId}/, creates borrow_item_photos.
 * Idempotent: skip an item+kind ທີ່ມີຮູບແລ້ວ.
 */
class ImportBorrowPhotos extends Command
{
    protected $signature = 'borrow:import-photos {manifest} {--dir= : base dir ຂອງ photo files (default = manifest dir/borrow_photos)}';

    protected $description = 'Import borrow item photos from a WIS export manifest';

    public function handle(): int
    {
        $manifestPath = $this->argument('manifest');
        if (! is_file($manifestPath)) {
            $this->error("ບໍ່ພົບ manifest: {$manifestPath}");

            return self::FAILURE;
        }
        $dir = $this->option('dir') ?: dirname($manifestPath).'/borrow_photos';
        $entries = json_decode(file_get_contents($manifestPath), true) ?: [];

        // group by record_number → item_index → kind
        $records = BorrowRecord::with('items')->get()->keyBy('request_number');
        $imported = 0;
        $skipped = 0;
        $missing = 0;
        $seen = [];

        foreach ($entries as $e) {
            $rec = $records->get($e['request_number'] ?? '');
            if (! $rec) {
                $missing++;

                continue;
            }
            $item = $rec->items->values()->get((int) ($e['item_index'] ?? -1));
            if (! $item) {
                $missing++;

                continue;
            }
            $kind = $e['kind'] === 'return' ? 'return' : 'take';
            $key = $item->id.':'.$kind;

            // idempotent: skip ຖ້າ item+kind ນີ້ມີຮູບແລ້ວ (ກ່ອນ import ນີ້)
            if (! isset($seen[$key]) && $item->photos()->where('kind', $kind)->exists()) {
                $seen[$key] = 'exists';
            }
            if (($seen[$key] ?? null) === 'exists') {
                $skipped++;

                continue;
            }

            $src = $dir.'/'.$e['file'];
            if (! is_file($src)) {
                $missing++;

                continue;
            }

            $n = (int) ($seen[$key] ?? 0);
            $dest = "borrow/{$rec->id}/{$item->id}/{$kind}_{$n}.jpg";
            Storage::disk('public')->put($dest, file_get_contents($src));
            $item->photos()->create(['kind' => $kind, 'path' => $dest, 'sort_order' => $n]);
            $seen[$key] = $n + 1;
            $imported++;
        }

        $this->newLine();
        $this->info("✓ Imported: {$imported} · Skipped (already had): {$skipped} · Missing: {$missing}");

        return self::SUCCESS;
    }
}
