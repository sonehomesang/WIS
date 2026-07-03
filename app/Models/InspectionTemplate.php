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

    /**
     * ຄືນ ຂໍ້ ກວດ ໃນ ຮູບແບບ ມາດຕະຖານ [{label, applies}].
     * ຮອງຮັບ ແມ່ແບບ ເກົ່າ (item ເປັນ string ລ້ວນ = applies 'both').
     *
     * @return array<int,array{label:string,applies:string}>
     */
    public function normalizedItems(): array
    {
        return collect($this->items ?? [])
            ->map(function ($it) {
                if (is_string($it)) {
                    return ['label' => trim($it), 'applies' => 'both'];
                }
                $applies = $it['applies'] ?? 'both';

                return [
                    'label' => trim((string) ($it['label'] ?? '')),
                    'applies' => in_array($applies, self::APPLIES, true) ? $applies : 'both',
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
}
