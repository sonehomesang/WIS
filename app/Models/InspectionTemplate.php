<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ແມ່ແບບ ການ ກວດກາ — ຊຸດ ເຊັກລິສ ຕໍ່ ປະເພດ ເຄື່ອງ. admin CRUD (Equipment › ແມ່ແບບ ກວດກາ).
 */
class InspectionTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'category', 'method', 'items', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'items' => 'array',
        'is_active' => 'boolean',
    ];

    /** ໃຊ້ ໄດ້ ກັບ ປະເພດ ໃດ. */
    public const APPLIES = ['both', 'ev', 'engine'];

    /** ຮອບ ການ ກວດ. */
    public const FREQUENCIES = ['pre_use', 'monthly', 'quarterly', 'semi_annual', 'annual'];

    /** ປ້າຍ ຮອບ (ພາສາ ລາວ). */
    public const FREQ_LABELS = [
        'pre_use' => 'ກ່ອນໃຊ້/ວັນ',
        'monthly' => 'ເດືອນ',
        'quarterly' => 'ໄຕມາດ',
        'semi_annual' => '6 ເດືອນ',
        'annual' => 'ປີ',
    ];

    /**
     * ຄືນ ຂໍ້ ກວດ ໃນ ຮູບແບບ ມາດຕະຖານ [{label, applies, freqs}].
     * ຮອງຮັບ ແມ່ແບບ ເກົ່າ (item ເປັນ string ລ້ວນ = applies 'both', freqs ວ່າງ = ທຸກ ຮອບ).
     *
     * @return array<int,array{label:string,applies:string,freqs:array<int,string>}>
     */
    public function normalizedItems(): array
    {
        return collect($this->items ?? [])
            ->map(function ($it) {
                if (is_string($it)) {
                    return ['label' => trim($it), 'applies' => 'both', 'freqs' => []];
                }
                $applies = $it['applies'] ?? 'both';
                $freqs = array_values(array_intersect(self::FREQUENCIES, (array) ($it['freqs'] ?? [])));

                return [
                    'label' => trim((string) ($it['label'] ?? '')),
                    'applies' => in_array($applies, self::APPLIES, true) ? $applies : 'both',
                    'freqs' => $freqs,
                ];
            })
            ->filter(fn ($x) => $x['label'] !== '')
            ->values()
            ->all();
    }

    /** ມີ ຂໍ້ ທີ່ ຂຶ້ນ ຕາມ ປະເພດ ນ້ຳມັນ (EV/Engine) ບໍ. */
    public function hasFuelTypes(): bool
    {
        return collect($this->normalizedItems())->contains(fn ($x) => $x['applies'] !== 'both');
    }

    /** ມີ ຂໍ້ ທີ່ ຕິດ ຮອບ ເວລາ ບໍ (ຖ້າ ມີ → ຟອມ ກວດ ໃຫ້ ເລືອກ ຮອບ ກ່ອນ). */
    public function hasFrequencies(): bool
    {
        return collect($this->normalizedItems())->contains(fn ($x) => ! empty($x['freqs']));
    }
}
