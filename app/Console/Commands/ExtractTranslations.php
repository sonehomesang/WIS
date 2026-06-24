<?php

namespace App\Console\Commands;

use App\Models\Translation;
use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

/**
 * Scans Blade views for hard-coded Lao/Thai phrases and seeds them into the
 * translations table (type=replace, target=source) so admins get a full,
 * editable catalogue of the app's wording. Idempotent: existing sources skip.
 */
class ExtractTranslations extends Command
{
    protected $signature = 'translations:extract {--prune : delete auto rows whose source no longer appears}';

    protected $description = 'Extract hard-coded Lao/Thai text from blades into the translations catalogue';

    public function handle(): int
    {
        $views = base_path('resources/views');
        $finder = Finder::create()->files()->in($views)->name('*.blade.php');

        $found = [];   // source => group
        foreach ($finder as $file) {
            $group = $this->group($file->getRelativePathname());
            $content = $file->getContents();
            foreach ($this->phrases($content) as $text) {
                if (! isset($found[$text])) {
                    $found[$text] = $group;
                }
            }
        }

        $created = 0;
        foreach ($found as $source => $group) {
            $row = Translation::firstOrCreate(
                ['type' => 'replace', 'source' => $source],
                ['target' => $source, 'group' => $group, 'is_active' => true, 'note' => 'auto']
            );
            if ($row->wasRecentlyCreated) {
                $created++;
            }
        }

        $pruned = 0;
        if ($this->option('prune')) {
            $pruned = Translation::where('type', 'replace')->where('note', 'auto')
                ->whereNotIn('source', array_keys($found))->delete();
        }

        $this->info("ພົບ {$pruned} ສະບັບ · ໃໝ່ {$created} · ລວມ catalogue ".count($found).' ຄຳ'.($pruned ? " · ลบ {$pruned}" : ''));

        return self::SUCCESS;
    }

    /** View name as group, e.g. livewire/borrow/index.blade.php → borrow.index. */
    private function group(string $rel): string
    {
        $g = str_replace(['\\', '/'], '.', $rel);
        $g = preg_replace('/\.blade\.php$/', '', $g);
        $g = preg_replace('/^livewire\./', '', $g);

        return mb_substr($g, 0, 64);
    }

    /** @return array<int,string> distinct cleaned phrases (Lao/Thai + English labels). */
    private function phrases(string $content): array
    {
        $out = [];

        // candidate chunks: text nodes between tags + quoted strings (attrs / php)
        $candidates = [];
        if (preg_match_all('/>([^<>{}@]+)</u', $content, $m)) {
            $candidates = array_merge($candidates, $m[1]);
        }
        if (preg_match_all('/([\'"])([^\'"{}<>\r\n]{2,120})\1/u', $content, $m)) {
            $candidates = array_merge($candidates, $m[2]);
        }

        foreach ($candidates as $raw) {
            $text = trim(preg_replace('/\s+/u', ' ', $raw));
            $text = trim($text, " \t·•—–|*");
            if ($text === '' || str_contains($text, '{{') || str_contains($text, '}}') || str_contains($text, '<?')) {
                continue;
            }
            if (mb_strlen($text) < 2 || mb_strlen($text) > 480) {
                continue;
            }

            $hasLao = (bool) preg_match('/[\x{0E00}-\x{0EFF}]/u', $text);
            if ($hasLao) {
                $out[$text] = true;            // any Lao/Thai phrase → keep
            } elseif ($this->isEnglishLabel($text)) {
                $out[$text] = true;            // clean English label → keep
            }
        }

        return array_keys($out);
    }

    /**
     * High-precision English label: starts with a capital, words are letters/
     * digits/&/+ joined by single spaces. Drops routes (settings.users),
     * classes (bg-white text-sm), wire:/x- bindings, snake_case, SVG paths.
     */
    private function isEnglishLabel(string $text): bool
    {
        return (bool) preg_match('/^[A-Z][A-Za-z0-9]*(?: [A-Za-z0-9&\/+]+)*$/', $text)
            && mb_strlen($text) <= 80;
    }
}
