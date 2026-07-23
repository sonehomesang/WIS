<?php

namespace App\Support;

use App\Models\Translation;
use Symfony\Component\Finder\Finder;

/**
 * ດຶງ ຄຳ hard-coded (ລາວ/ອັງກິດ) ຈາກ blade ທັງໝົດ ເຂົ້າ catalogue ແປ (type=replace,
 * target=source, note=auto). idempotent — ຄຳ ທີ່ ມີ ແລ້ວ ຂ້າມ (ບໍ່ ທັບ ຄຳ ແປ ຂອງ admin).
 * ໃຊ້ ຮ່ວມ ໂດຍ command `translations:extract` ແລະ ປຸ່ມ "ດຶງ ຄຳ ໃໝ່" ໃນ Settings › Translations.
 */
class TranslationExtractor
{
    /**
     * @return array{created:int, total:int, pruned:int}
     */
    public static function run(bool $prune = false): array
    {
        $finder = Finder::create()->files()->in(base_path('resources/views'))->name('*.blade.php');

        $found = [];   // source => group
        foreach ($finder as $file) {
            $group = self::group($file->getRelativePathname());
            foreach (self::phrases($file->getContents()) as $text) {
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
        if ($prune) {
            $pruned = Translation::where('type', 'replace')->where('note', 'auto')
                ->whereNotIn('source', array_keys($found))->delete();
        }

        return ['created' => $created, 'total' => count($found), 'pruned' => $pruned];
    }

    /** View name as group, e.g. livewire/borrow/index.blade.php → borrow.index. */
    private static function group(string $rel): string
    {
        $g = str_replace(['\\', '/'], '.', $rel);
        $g = preg_replace('/\.blade\.php$/', '', $g);
        $g = preg_replace('/^livewire\./', '', $g);

        return mb_substr($g, 0, 64);
    }

    /** @return array<int,string> distinct cleaned phrases (Lao/Thai + English labels). */
    private static function phrases(string $content): array
    {
        $out = [];

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

            if (preg_match('/[\x{0E00}-\x{0EFF}]/u', $text)) {
                $out[$text] = true;            // any Lao/Thai phrase
            } elseif (self::isEnglishLabel($text)) {
                $out[$text] = true;            // clean English label
            }
        }

        return array_keys($out);
    }

    private static function isEnglishLabel(string $text): bool
    {
        return (bool) preg_match('/^[A-Z][A-Za-z0-9]*(?: [A-Za-z0-9&\/+]+)*$/', $text)
            && mb_strlen($text) <= 80;
    }
}
