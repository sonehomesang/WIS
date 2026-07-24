<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'equipment_id', 'template_id', 'source_maintenance_id', 'maintenance_date', 'type', 'title', 'description',
        'performed_by', 'cost', 'frequency', 'next_service_date', 'status', 'checklist', 'notes', 'photos', 'created_by',
        'deleted_reason', 'deleted_by',
    ];

    /** ຜູ້ ທີ່ ລຶບ ບັນທຶກ ນີ້ (ສຳລັບ deleted log). */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /** ໃບ ສ້ອມ (CM) ທີ່ ຍັງ ບໍ່ ແລ້ວ (planned/in_progress). ໃຊ້ dashboard queue (C3). */
    public function scopeOpenRepairs(Builder $q): Builder
    {
        return $q->where('type', 'repair')->whereIn('status', ['planned', 'in_progress']);
    }

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

    /** ໃບ ບຳລຸງ/ກວດ ຕົ້ນທາງ ທີ່ ພົບ NG (ສຳລັບ ໃບ ສ້ອມ CM). */
    public function sourceMaintenance(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_maintenance_id');
    }

    /** ໃບ ສ້ອມ (CM) ທີ່ ຖືກ ສ້າງ ຈາກ ຂໍ້ NG ຂອງ ໃບ ນີ້. */
    public function correctiveRepairs(): HasMany
    {
        return $this->hasMany(self::class, 'source_maintenance_id');
    }
}
