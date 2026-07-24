<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Uom extends Model
{
    use SoftDeletes;

    protected $table = 'uoms';

    protected $fillable = ['slug', 'name', 'name_en', 'is_active', 'created_by', 'updated_by', 'deleted_reason', 'deleted_by'];

    protected $casts = ['is_active' => 'boolean'];

    /** ຜູ້ ທີ່ ລຶບ ໜ່ວຍວັດ ນີ້ (ສຳລັບ deleted log). */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
