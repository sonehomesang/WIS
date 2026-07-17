<?php

namespace App\Support;

/**
 * ເລກ ເວີຊັ່ນ + ເລກ ອັບເດດ ຂອງ ແອັບ ສຳລັບ ສະແດງ ໃນ header.
 * - TAG: ຕັ້ງ ເອງ (bump ຕອນ milestone).
 * - commit(): git short hash ຂອງ ໂຄ້ດ ທີ່ deploy (ອ່ານ ຈາກ .git ໂດຍ ກົງ — ບໍ່ ຕ້ອງ ຣັນ git).
 */
class AppVersion
{
    /** ເລກ ເວີຊັ່ນ ຫຼັກ — ປ່ຽນ ບ່ອນ ນີ້ ຕອນ ອອກ ຮຸ່ນ ໃໝ່. */
    public const TAG = '1.0';

    /** git short hash ຂອງ commit ທີ່ ກຳລັງ ແລ່ນ (null ຖ້າ ບໍ່ ມີ .git). */
    public static function commit(): ?string
    {
        $head = base_path('.git/HEAD');
        if (! is_file($head)) {
            return null;
        }
        $ref = trim((string) @file_get_contents($head));

        // detached HEAD → HEAD ຄື hash ໂດຍ ກົງ
        if (! str_starts_with($ref, 'ref: ')) {
            return $ref !== '' ? substr($ref, 0, 7) : null;
        }

        $branch = substr($ref, 5);                    // refs/heads/main
        $loose = base_path('.git/'.$branch);
        if (is_file($loose)) {
            $hash = trim((string) @file_get_contents($loose));

            return $hash !== '' ? substr($hash, 0, 7) : null;
        }

        // fresh clone → ref ຢູ່ ໃນ packed-refs
        $packed = base_path('.git/packed-refs');
        if (is_file($packed)) {
            foreach (@file($packed) ?: [] as $line) {
                if (str_ends_with(trim($line), ' '.$branch)) {
                    return substr(trim(explode(' ', $line)[0]), 0, 7);
                }
            }
        }

        return null;
    }

    /** ວັນທີ ອັບເດດ ຫຼ້າສຸດ (ຕອນ git pull/clone ຄັ້ງ ຫຼ້າສຸດ) — d/m/y. */
    public static function updatedAt(): ?string
    {
        $head = base_path('.git/HEAD');
        if (! is_file($head)) {
            return null;
        }
        $ref = trim((string) @file_get_contents($head));
        $path = str_starts_with($ref, 'ref: ') ? base_path('.git/'.substr($ref, 5)) : $head;
        $time = is_file($path) ? @filemtime($path) : @filemtime($head);

        return $time ? date('d/m/y', $time) : null;
    }

    /** ປ້າຍ ສະແດງ: "v1.0 · 6cbc1ba" (ຫຼື ພຽງ "v1.0" ຖ້າ ບໍ່ ມີ git). */
    public static function label(): string
    {
        $commit = self::commit();

        return $commit ? 'v'.self::TAG.' · '.$commit : 'v'.self::TAG;
    }
}
