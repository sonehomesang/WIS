<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** master ປະເພດ ເຄື່ອງມື/ເຄື່ອງຈັກ — super_admin/admin ກຳນົດ. */
class EquipmentCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'is_active', 'sort_order', 'created_by', 'updated_by', 'deleted_reason', 'deleted_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** ຜູ້ ທີ່ ລຶບ ປະເພດ ນີ້ (ສຳລັບ deleted log). */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
