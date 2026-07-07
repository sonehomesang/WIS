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
        'name', 'equipment_id', 'category', 'method', 'items', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'items' => 'array',
        'is_active' => 'boolean',
    ];

    /** ຮອບ ບຳລຸງ — ໃຊ້ ເປັນ ຟິລເຕີ ຄັດ ລາຍການ ຕອນ ລົງມື. */
    public const FREQUENCIES = ['daily', 'monthly', 'quarterly', 'annual'];

    /** ປ້າຍ ຮອບ (ພາສາ ລາວ). */
    public const FREQ_LABELS = [
        'daily' => 'ວັນ',
        'monthly' => 'ເດືອນ',
        'quarterly' => 'ໄຕມາດ',
        'annual' => 'ປີ',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * ຄືນ ຂໍ້ ເຊັກລິສ ໃນ ຮູບແບບ ມາດຕະຖານ [{label, freqs}].
     * ຮອງຮັບ ແມ່ແບບ ເກົ່າ (item ເປັນ string ລ້ວນ = freqs ວ່າງ = ທຸກ ຮອບ).
     *
     * @return array<int,array{label:string,freqs:array<int,string>}>
     */
    public function normalizedItems(): array
    {
        return collect($this->items ?? [])
            ->map(function ($it) {
                if (is_string($it)) {
                    return ['label' => trim($it), 'freqs' => []];
                }
                $freqs = array_values(array_intersect(self::FREQUENCIES, (array) ($it['freqs'] ?? [])));

                return [
                    'label' => trim((string) ($it['label'] ?? '')),
                    'freqs' => $freqs,
                ];
            })
            ->filter(fn ($x) => $x['label'] !== '')
            ->values()
            ->all();
    }

    /** ມີ ຂໍ້ ທີ່ ຕິດ ຮອບ ເວລາ ບໍ (ຖ້າ ມີ → ຟອມ ບຳລຸງ ໃຫ້ ເລືອກ ຮອບ ກ່ອນ). */
    public function hasFrequencies(): bool
    {
        return collect($this->normalizedItems())->contains(fn ($x) => ! empty($x['freqs']));
    }
}
