<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentInspection extends Model
{
    protected $fillable = [
        'equipment_id', 'template_id', 'fuel_type', 'inspected_at', 'inspector_name', 'result', 'score',
        'checklist', 'notes', 'next_due_date', 'photo_path', 'created_by',
    ];

    protected $casts = [
        'inspected_at' => 'datetime',
        'next_due_date' => 'date',
        'score' => 'integer',
        'checklist' => 'array',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(InspectionTemplate::class, 'template_id');
    }
}
