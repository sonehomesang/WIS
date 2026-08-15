<?php

namespace App\Console\Commands;

use App\Models\ExpoContact;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Moves existing Expo business-card images off the public disk onto the private
 * (local) disk, closing the pre-hardening PII exposure. Idempotent: safe to re-run.
 */
class PrivatizeExpoCards extends Command
{
    protected $signature = 'expo:privatize-cards {--dry-run : List what would move without changing anything}';

    protected $description = 'Move Expo business-card images from the public disk to the private disk';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $moved = 0;
        $already = 0;
        $missing = 0;

        $rows = ExpoContact::whereNotNull('business_card_path')->get();
        $this->info("Scanning {$rows->count()} contact card(s)…");

        foreach ($rows as $c) {
            $path = $c->business_card_path;

            if (Storage::disk('local')->exists($path)) {
                $already++;

                continue;
            }
            if (! Storage::disk('public')->exists($path)) {
                $missing++;
                $this->warn("  missing on both disks: {$path}");

                continue;
            }

            if ($dry) {
                $this->line("  would move: {$path}");
                $moved++;

                continue;
            }

            Storage::disk('local')->put($path, Storage::disk('public')->get($path));
            Storage::disk('public')->delete($path);
            $moved++;
        }

        $this->newLine();
        $this->info(($dry ? '[dry-run] ' : '')."moved={$moved}  already-private={$already}  missing={$missing}");

        return self::SUCCESS;
    }
}
