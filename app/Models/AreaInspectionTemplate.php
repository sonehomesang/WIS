<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** ແມ່ແບບ ເຊັກລິສ ກວດ ສະຖານທີ່ (Area/Workplace Inspection). admin CRUD. */
class AreaInspectionTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'frequency', 'items', 'is_active', 'created_by', 'updated_by', 'deleted_reason', 'deleted_by',
    ];

    protected $casts = [
        'items' => 'array',
        'is_active' => 'boolean',
    ];

    /** ຮອບ ການ ກວດ. */
    public const FREQUENCIES = ['daily', 'weekly', 'monthly', 'quarterly', 'annual'];

    /** ປ້າຍ ຮອບ (ພາສາ ລາວ). */
    public const FREQ_LABELS = [
        'daily' => 'ວັນ',
        'weekly' => 'ອາທິດ',
        'monthly' => 'ເດືອນ',
        'quarterly' => 'ໄຕມາດ',
        'annual' => 'ປີ',
    ];

    /**
     * ຄືນ ຂໍ້ ກວດ ໃນ ຮູບແບບ ມາດຕະຖານ [{label, requirement}].
     * ຮອງຮັບ item ເປັນ string ລ້ວນ (= label, requirement ວ່າງ).
     *
     * @return array<int,array{label:string,requirement:string}>
     */
    public function normalizedItems(): array
    {
        return collect($this->items ?? [])
            ->map(function ($it) {
                if (is_string($it)) {
                    return ['label' => trim($it), 'requirement' => ''];
                }

                return [
                    'label' => trim((string) ($it['label'] ?? '')),
                    'requirement' => trim((string) ($it['requirement'] ?? '')),
                ];
            })
            ->filter(fn ($x) => $x['label'] !== '')
            ->values()
            ->all();
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
