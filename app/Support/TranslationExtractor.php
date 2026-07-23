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

        // 1) Text nodes between tags — rendered, user-visible content. These are
        //    display text, so accept Lao OR any short English display value
        //    (incl. lowercase dropdown/enum options like "available", "low-stock").
        //    The (?<!-) lookbehind stops a PHP arrow "->" from opening a bogus
        //    text node (eg. "@if($r->deleted_reason)" would otherwise leak the
        //    code fragment "deleted_reason)" into the catalogue).
        if (preg_match_all('/(?<!-)>([^<>@]+)</u', $content, $m)) {
            foreach ($m[1] as $raw) {
                // A text node may interleave static text with {{ }} bindings, eg.
                // a filter header "ທຸກ ໝວດ (ໜ້າ) — {{ $total }} ຄຳ". Split on the
                // bindings so the static Lao/English fragments around them are
                // still captured (clean() drops any residual { } from {!! !!}/JS).
                foreach (preg_split('/\{\{.*?\}\}/us', $raw) as $part) {
                    if (($text = self::clean($part)) !== null && (self::hasLao($text) || self::isDisplayValue($text))) {
                        $out[$text] = true;
                    }
                }
            }
        }

        // 2) Quoted strings — may be labels, but also class names / keys / wire
        //    targets, so stay strict: Lao text OR a Title-case English label only.
        if (preg_match_all('/([\'"])([^\'"{}<>\r\n]{2,120})\1/u', $content, $m)) {
            foreach ($m[2] as $raw) {
                if (($text = self::clean($raw)) !== null && (self::hasLao($text) || self::isEnglishLabel($text))) {
                    $out[$text] = true;
                }
            }
        }

        return array_keys($out);
    }

    /** Normalise a raw candidate; null if it should be skipped. */
    private static function clean(string $raw): ?string
    {
        // Collapse whitespace, then strip leading/trailing decoration chars.
        // NOTE: trim() is byte-based, so a char-list with multibyte glyphs
        // (·•—–) would shear a trailing UTF-8 byte off Lao words that end in
        // a colliding byte (eg. ດ = e0 ba 94 vs — = e2 80 94), corrupting the
        // string. Use a /u regex so trimming operates on whole codepoints.
        $text = trim(preg_replace('/\s+/u', ' ', $raw));
        $text = preg_replace('/^[·•—–|*\s]+|[·•—–|*\s]+$/u', '', $text);

        // Reject leftover template braces ({!! !!}, JS/CSS { }, stray {{ }}) or PHP tags.
        if ($text === '' || str_contains($text, '{') || str_contains($text, '}') || str_contains($text, '<?')) {
            return null;
        }
        if (mb_strlen($text) < 2 || mb_strlen($text) > 480) {
            return null;
        }

        return $text;
    }

    private static function hasLao(string $text): bool
    {
        return (bool) preg_match('/[\x{0E00}-\x{0EFF}]/u', $text);
    }

    /** Strict Title-case English label — safe for quoted attribute strings. */
    private static function isEnglishLabel(string $text): bool
    {
        return (bool) preg_match('/^[A-Z][A-Za-z0-9]*(?: [A-Za-z0-9&\/+]+)*$/', $text)
            && (bool) preg_match('/[A-Za-z]{2}/', $text)   // reject SVG path data ("M6 18L18 6")
            && mb_strlen($text) <= 80;
    }

    /**
     * Any short English display value — dropdown options, enum labels, statuses.
     * Broader than isEnglishLabel (accepts lowercase, _-/() ) but still must be
     * word-like with ≥2 letters, so pure numbers/symbols/code are rejected.
     */
    private static function isDisplayValue(string $text): bool
    {
        // A spaceless camelCase token (transactionScope, catalogScope) is a code
        // identifier, not a label — reject a lower→UPPER hump with no space.
        // "eForm" survives (only 1 lowercase before the hump).
        if (! str_contains($text, ' ') && preg_match('/[a-z]{2,}[A-Z]/', $text)) {
            return false;
        }

        return (bool) preg_match('/^[A-Za-z][A-Za-z0-9()\/&+ _.-]*$/', $text)
            && (bool) preg_match('/[A-Za-z]{2}/', $text)   // ≥2 consecutive letters (a real word)
            && mb_strlen($text) <= 80;
    }
}
