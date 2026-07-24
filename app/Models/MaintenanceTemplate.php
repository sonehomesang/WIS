<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ແມ່ແບບ ບຳລຸງຮັກສາ — ຊຸດ ເຊັກລິສ ວຽກ ບຳລຸງ ຕໍ່ ເຄື່ອງ ໜຶ່ງ. admin CRUD (Equipment › ແມ່ແບບ ບຳລຸງ).
 */
class MaintenanceTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'equipment_id', 'category', 'method', 'items', 'is_active', 'created_by', 'updated_by', 'deleted_reason', 'deleted_by',
    ];

    /** ຜູ້ ທີ່ ລຶບ ແມ່ແບບ ນີ້ (ສຳລັບ deleted log). */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    protected $casts = [
        'items' => 'array',
        'is_active' => 'boolean',
    ];

    /** ຮອບ ບຳລຸງ ຕາມ ຊົ່ວໂມງ — ໃຊ້ ເປັນ ຟິລເຕີ ຄັດ ລາຍການ ຕອນ ລົງມື. */
    public const FREQUENCIES = ['daily', 'monthly', 'quarterly', 'semi_annual', 'annual'];

    /** ປ້າຍ ຮອບ (ພາສາ ລາວ). */
    public const FREQ_LABELS = [
        'daily' => 'ວັນ',
        'monthly' => 'ເດືອນ',
        'quarterly' => 'ໄຕມາດ',
        'semi_annual' => '6 ເດືອນ',
        'annual' => 'ປີ',
    ];

    /** ຊົ່ວໂມງ ທຽບ ຕໍ່ ຮອບ (ສະແດງ ໃຫ້ ຮູ້). */
    public const FREQ_HOURS = [
        'daily' => '8h',
        'monthly' => '200h',
        'quarterly' => '600h',
        'semi_annual' => '1200h',
        'annual' => '2400h',
    ];

    /** ການ ກະທຳ ຕໍ່ ຮອບ: ກວດ (C) ຫຼື ປ່ຽນ (X). */
    public const ACTIONS = [
        'C' => 'ກວດ',
        'X' => 'ປ່ຽນ',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * ຄືນ ຂໍ້ ເຊັກລິສ ຮູບແບບ ມາດຕະຖານ [{label, remark, cycles:{cycle => 'C'|'X'}}].
     * ຮອງຮັບ ຮູບແບບ ເກົ່າ: string ລ້ວນ, ຫຼື {label, freqs:[...]} (freqs → cycle=ກວດ).
     *
     * @return array<int,array{label:string,remark:string,cycles:array<string,string>}>
     */
    public function normalizedItems(): array
    {
        return collect($this->items ?? [])
            ->map(function ($it) {
                if (is_string($it)) {
                    return ['label' => trim($it), 'remark' => '', 'cycles' => []];
                }

                $raw = [];
                if (isset($it['cycles']) && is_array($it['cycles'])) {
                    $raw = $it['cycles'];
                } elseif (isset($it['freqs']) && is_array($it['freqs'])) {
                    // ເກົ່າ: freqs array → ຖື ເປັນ ກວດ (C) ທຸກ ຮອບ ທີ່ ຕິດ.
                    foreach ($it['freqs'] as $f) {
                        $raw[$f] = 'C';
                    }
                }

                // ຄັດ ໃຫ້ ຢູ່ ໃນ ຮອບ + ຄ່າ ທີ່ ຮັບຮອງ, ຮຽງ ຕາມ ລຳດັບ ຮອບ.
                $cycles = [];
                foreach (self::FREQUENCIES as $f) {
                    $v = $raw[$f] ?? null;
                    if (in_array($v, ['C', 'X'], true)) {
                        $cycles[$f] = $v;
                    }
                }

                return [
                    'label' => trim((string) ($it['label'] ?? '')),
                    'remark' => trim((string) ($it['remark'] ?? '')),
                    'cycles' => $cycles,
                ];
            })
            ->filter(fn ($x) => $x['label'] !== '')
            ->values()
            ->all();
    }

    /** ຄືນ ລາຍການ ທີ່ ຕ້ອງ ເຮັດ ໃນ ຮອບ ໃດ ໜຶ່ງ (ພ້ອມ action C/X). ໃຊ້ ຕອນ ລົງມື. */
    public function itemsForCycle(string $cycle): array
    {
        return collect($this->normalizedItems())
            ->filter(fn ($x) => isset($x['cycles'][$cycle]))
            ->map(fn ($x) => ['label' => $x['label'], 'remark' => $x['remark'], 'action' => $x['cycles'][$cycle]])
            ->values()
            ->all();
    }
}
