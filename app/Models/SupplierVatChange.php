<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierVatChange extends Model
{
    public $timestamps = false;

    protected $fillable = ['supplier_id', 'old_rate', 'new_rate', 'reason', 'changed_by', 'changed_at'];

    protected $casts = [
        'old_rate' => 'decimal:2',
        'new_rate' => 'decimal:2',
        'changed_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
