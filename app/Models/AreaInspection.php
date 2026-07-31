<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** ບັນທຶກ ການ ກວດ ສະຖານທີ່ (Area/Workplace Inspection). ຜູກ Facilities location ຫຼື free-text. */
class AreaInspection extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'inspected_on' => 'date',
        'next_due_date' => 'date',
        'inspectors' => 'array',
        'checklist' => 'array',
        'acknowledged_at' => 'datetime',
    ];

    /** ສະຖານະ ຕໍ່ ຂໍ້: C=ຖືກຕ້ອງ · NC=ບໍ່ຖືກຕ້ອງ · NA=ບໍ່ກ່ຽວ. */
    public const STATUSES = ['C', 'NC', 'NA'];

    public const STATUS_LABELS = [
        'C' => 'ຖືກຕ້ອງ (C)',
        'NC' => 'ບໍ່ຖືກຕ້ອງ (NC)',
        'NA' => 'ບໍ່ກ່ຽວ (NA)',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(AreaInspectionTemplate::class, 'template_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class, 'building_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /** ຊື່ ສະຖານທີ່ ສຳລັບ ສະແດງ — label ທີ່ ພິມ, ຫຼື ໄລ່ ຈາກ Facilities. */
    public function locationName(): string
    {
        if ($this->location_label) {
            return $this->location_label;
        }
        $parts = array_filter([
            $this->room?->name,
            $this->building?->name,
            $this->location?->name,
        ]);

        return $parts ? implode(' · ', $parts) : '—';
    }

    /** ຈຳນວນ ຂໍ້ ທີ່ NC (ບໍ່ຜ່ານ). */
    public function ncCount(): int
    {
        return collect($this->checklist ?? [])->where('status', 'NC')->count();
    }
}
