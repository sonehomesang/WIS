<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ທະບຽນ ເຄື່ອງມື/ເຄື່ອງຈັກ (Equipment & Tools register). Inspection / usage /
 * maintenance logs ຈະ ເປັນ ໂມເດວ ແຍກ ທີ່ ອ້າງອີງ equipment_id (ແທັບ 2-4).
 */
class Equipment extends Model
{
    use SoftDeletes;

    protected $table = 'equipment';

    protected $fillable = [
        'asset_code', 'fixed_asset_no', 'name', 'category', 'brand_model', 'serial_no',
        'location', 'responsible_name', 'status', 'photo_path', 'purchase_date',
        'notes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
    ];
}
