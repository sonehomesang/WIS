<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierContract extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'supplier_id', 'contract_number', 'sign_date', 'effective_date', 'expiry_date', 'renewal_date',
        'status', 'notes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'sign_date' => 'date',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'renewal_date' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
