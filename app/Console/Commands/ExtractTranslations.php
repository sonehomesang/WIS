<?php

namespace App\Console\Commands;

use App\Support\TranslationExtractor;
use Illuminate\Console\Command;

/**
 * Scans Blade views for hard-coded Lao/Thai phrases and seeds them into the
 * translations table (type=replace, target=source) so admins get a full,
 * editable catalogue of the app's wording. Idempotent: existing sources skip.
 * Logic lives in App\Support\TranslationExtractor (shared with the UI button).
 */
class ExtractTranslations extends Command
{
    protected $signature = 'translations:extract {--prune : delete auto rows whose source no longer appears}';

    protected $description = 'Extract hard-coded Lao/Thai text from blades into the translations catalogue';

    public function handle(): int
    {
        $r = TranslationExtractor::run((bool) $this->option('prune'));

        $this->info("ໃໝ່ {$r['created']} · ລວມ catalogue {$r['total']} ຄຳ".($r['pruned'] ? " · ລຶບ {$r['pruned']}" : ''));

        return self::SUCCESS;
    }
}
