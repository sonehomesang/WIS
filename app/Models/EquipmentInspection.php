<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentInspection extends Model
{
    protected $fillable = [
        'equipment_id', 'inspected_at', 'inspector_name', 'result',
        'notes', 'next_due_date', 'photo_path', 'created_by',
    ];

    protected $casts = [
        'inspected_at' => 'date',
        'next_due_date' => 'date',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
