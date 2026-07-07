<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** ບັນທຶກ ການ ບຳລຸງຮັກສາ/ຊ່ອມແປງ ຂອງ ເຄື່ອງ. */
class EquipmentMaintenance extends Model
{
    use SoftDeletes;

    /** ປະເພດ ບຳລຸງ. */
    public const TYPES = [
        'preventive' => 'ປ້ອງກັນ (Preventive)',
        'repair' => 'ແກ້ໄຂ/ຊ່ອມ (Repair)',
        'service' => 'Service ຕາມ ຮອບ',
        'other' => 'ອື່ນໆ',
    ];

    /** ຮອບ service (ບໍ່ ມີ pre_use — ບຳລຸງ ບໍ່ ກວດ ກ່ອນ ໃຊ້ ທຸກ ວັນ). */
    public const FREQUENCIES = ['monthly', 'quarterly', 'semi_annual', 'annual'];

    public const FREQ_LABELS = [
        'monthly' => 'ເດືອນ',
        'quarterly' => 'ໄຕມາດ',
        'semi_annual' => '6 ເດືອນ',
        'annual' => 'ປີ',
    ];

    public const STATUSES = [
        'planned' => 'ວາງແຜນ',
        'in_progress' => 'ກຳລັງ ເຮັດ',
        'done' => 'ແລ້ວ',
    ];

    protected $fillable = [
        'equipment_id', 'template_id', 'maintenance_date', 'type', 'title', 'description', 'performed_by',
        'cost', 'frequency', 'next_service_date', 'status', 'checklist', 'notes', 'photos', 'created_by',
    ];

    protected $casts = [
        'maintenance_date' => 'date',
        'next_service_date' => 'date',
        'cost' => 'decimal:2',
        'checklist' => 'array',
        'photos' => 'array',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTemplate::class, 'template_id');
    }
}
