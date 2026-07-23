<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipmentInspection extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'equipment_id', 'template_id', 'fuel_type', 'frequency', 'inspected_at', 'inspector_name', 'result', 'score',
        'checklist', 'notes', 'next_due_date', 'photo_path', 'photos', 'created_by', 'deleted_reason', 'deleted_by',
    ];

    protected $casts = [
        'inspected_at' => 'datetime',
        'next_due_date' => 'date',
        'score' => 'integer',
        'checklist' => 'array',
        'photos' => 'array',
    ];

    /** ຮູບ ຫຼັກຖານ ທັງ ໝົດ — ໃຊ້ photos array; ຖ້າ ບໍ່ ມີ ຕົກ ໄປ photo_path ເກົ່າ. */
    public function allPhotos(): array
    {
        if (! empty($this->photos)) {
            return array_values($this->photos);
        }

        return $this->photo_path ? [$this->photo_path] : [];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(InspectionTemplate::class, 'template_id');
    }

    /** ຜູ້ ທີ່ ລຶບ ໃບ ກວດ ນີ້ (ສຳລັບ deleted log). */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
