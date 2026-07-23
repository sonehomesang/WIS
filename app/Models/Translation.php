<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Admin-editable wording overrides (Phase 6.11).
 *
 * - type=replace: ຄຳຜິດ → ຄຳຖືກ, ໃຊ້ໂດຍ ReplaceTerms middleware (ທົ່ວແອັບ)
 * - type=term:    key → value, ໃຊ້ໂດຍ @term('key', 'default')
 *
 * Both maps are cached forever and busted on any write.
 */
class Translation extends Model
{
    protected $fillable = ['type', 'group', 'source', 'target', 'note', 'is_active', 'updated_by'];

    protected $casts = ['is_active' => 'boolean'];

    public const CACHE_REPLACE = 'translations.replace';

    public const CACHE_TERM = 'translations.term';

    protected static function booted(): void
    {
        $bust = function () {
            Cache::forget(self::CACHE_REPLACE);
            Cache::forget(self::CACHE_TERM);
        };
        static::saved($bust);
        static::deleted($bust);
    }

    /** @return array<string,string> source→target, active, longest source first. */
    public static function replaceMap(): array
    {
        return Cache::rememberForever(self::CACHE_REPLACE, function () {
            $rows = static::query()->where('type', 'replace')->where('is_active', true)
                ->whereNotNull('target')->where('source', '!=', '')
                ->whereColumn('target', '!=', 'source')   // identity rows = no-op, skip
                ->pluck('target', 'source')->all();
            // Strip any HTML from the admin-entered target before it is injected
            // into rendered pages — prevents stored XSS via the replace middleware.
            $rows = array_map(fn ($t) => strip_tags((string) $t), $rows);
            // longest source first so phrases win over their sub-words
            uksort($rows, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

            return $rows;
        });
    }

    /** @return array<string,string> key→value for active term overrides. */
    public static function termMap(): array
    {
        return Cache::rememberForever(self::CACHE_TERM, fn () => static::query()
            ->where('type', 'term')->where('is_active', true)->whereNotNull('target')
            ->pluck('target', 'source')->all());
    }

    /** Resolve a term key to its override, falling back to the given default. */
    public static function term(string $key, string $default = ''): string
    {
        return static::termMap()[$key] ?? $default;
    }

    /**
     * Attributes whose values are display text and safe to translate.
     * wire:confirm is the Livewire confirmation-dialog prompt shown to the user,
     * so it belongs here alongside the standard HTML display attributes.
     */
    public const SAFE_ATTRS = ['title', 'placeholder', 'alt', 'aria-label', 'wire:confirm'];

    /**
     * Apply active replace pairs — but only inside visible text nodes and a
     * whitelist of display attributes. <script>/<style>/comments and all other
     * tag internals (class, value, wire:*, x-*) are left untouched, so English
     * sources can't corrupt code, option values, or CSS class names.
     */
    public static function applyReplacements(string $html): string
    {
        $map = static::replaceMap();
        if (! $map) {
            return $html;
        }

        // 1. Protect script/style/comment blocks.
        $stash = [];
        $html = preg_replace_callback(
            '#<script\b[^>]*>.*?</script>|<style\b[^>]*>.*?</style>|<!--.*?-->#is',
            function ($m) use (&$stash) {
                $key = "\x00".count($stash)."\x00";
                $stash[$key] = $m[0];

                return $key;
            },
            $html
        );

        // 2. Walk tag/text segments; translate text nodes + safe attributes.
        $attrs = implode('|', self::SAFE_ATTRS);
        $parts = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        $out = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if ($part[0] === '<' && str_ends_with($part, '>')) {
                $out .= preg_replace_callback(
                    '/\b('.$attrs.')="([^"]*)"/i',
                    // encode any " a replacement target might introduce → no attribute breakout
                    fn ($m) => $m[1].'="'.str_replace('"', '&quot;', strtr($m[2], $map)).'"',
                    $part
                );
            } else {
                $out .= strtr($part, $map);
            }
        }

        // 3. Restore protected blocks.
        return strtr($out, $stash);
    }
}
