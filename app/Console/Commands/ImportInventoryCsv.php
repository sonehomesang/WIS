<?php

namespace App\Console\Commands;

use App\Imports\InventoryCsvImporter;
use Illuminate\Console\Command;

class ImportInventoryCsv extends Command
{
    protected $signature = 'inventory:import-csv {path : ເສັ້ນທາງໄຟລ໌ CSV} {--by= : user id ສຳລັບ created_by/updated_by}';

    protected $description = 'Bulk import inventory items ຈາກ CSV (17K+ rows, idempotent by slug)';

    public function handle(): int
    {
        $path = $this->argument('path');
        if (! is_file($path)) {
            $this->error("ບໍ່ພົບໄຟລ໌: {$path}");

            return self::FAILURE;
        }

        $this->info("Importing from {$path} ...");
        $userId = $this->option('by') !== null ? (int) $this->option('by') : null;

        $result = (new InventoryCsvImporter)->import($path, $userId);

        $this->newLine();
        $this->info("✓ Imported: {$result['imported']} · Skipped: {$result['skipped']}");

        if ($result['errors'] !== []) {
            $this->warn(count($result['errors']).' ຂໍ້ຄວາມ (ສະແດງ 20 ທຳອິດ):');
            foreach (array_slice($result['errors'], 0, 20) as $e) {
                $this->line('  - '.$e);
            }
        }

        return self::SUCCESS;
    }
}
