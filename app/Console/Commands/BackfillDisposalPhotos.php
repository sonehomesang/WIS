<?php

namespace App\Console\Commands;

use App\Models\DepositItem;
use App\Models\DisposalItem;
use App\Models\Equipment;
use App\Models\InventoryItem;
use Illuminate\Console\Command;

/**
 * Pull source-register photos onto disposal items that were created BEFORE the
 * auto-pull-photos feature (Disposal\Create::grabSourcePhotos). By default only
 * touches items with no photos yet; --force re-pulls for every sourced item.
 */
class BackfillDisposalPhotos extends Command
{
    protected $signature = 'disposal:backfill-photos {--force : re-pull even for items that already have photos}';

    protected $description = 'Pull source-register (Deposit/Equipment/Inventory) photos onto existing disposal items';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $query = DisposalItem::query()
            ->whereNotNull('source_id')
            ->whereIn('source_type', ['deposit', 'equipment', 'inventory']);

        if (! $force) {
            $query->where(fn ($q) => $q->whereNull('photos')->orWhere('photos', '[]'));
        }

        $items = $query->get();
        $updated = 0;
        $skipped = 0;

        foreach ($items as $it) {
            $paths = $this->sourcePhotos($it->source_type, (int) $it->source_id);
            if (empty($paths)) {
                $skipped++;

                continue;
            }
            $it->update(['photos' => $paths]);
            $updated++;
            $this->line("  ✓ #{$it->id} {$it->item_name} ← ".count($paths).' photo(s) from '.$it->source_type);
        }

        $this->info("Backfilled {$updated} disposal item(s); {$skipped} sourced item(s) had no source photos.");

        return self::SUCCESS;
    }

    /**
     * Photo paths of the source register item (≤6). Mirrors
     * Disposal\Create::grabSourcePhotos so backfilled and freshly-pulled items match.
     *
     * @return array<int, string>
     */
    protected function sourcePhotos(string $source, int $id): array
    {
        $paths = match ($source) {
            'deposit' => optional(DepositItem::with('photos')->find($id))?->photos->pluck('path')->all() ?? [],
            'inventory' => optional(InventoryItem::with('photos')->find($id))?->photos->pluck('path')->all() ?? [],
            'equipment' => (function () use ($id) {
                $e = Equipment::with('photos')->find($id);
                if (! $e) {
                    return [];
                }
                $p = $e->photos->pluck('path')->all();

                return $p ?: ($e->photo_path ? [$e->photo_path] : []);
            })(),
            default => [],
        };

        return array_slice(array_values(array_filter($paths)), 0, 6);
    }
}
