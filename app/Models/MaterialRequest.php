<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialRequest extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'total' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_enabled' => 'boolean',
        'vat_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'invoice_received' => 'boolean',
        'delivery_note_received' => 'boolean',
        'spec_match' => 'boolean',
        'approved_at' => 'datetime',
        'validated_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'received_at' => 'datetime',
        'completed_at' => 'datetime',
        'planned_delivery_date' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MaterialRequestItem::class, 'record_id')->orderBy('sort_order')->orderBy('id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(MaterialRequestHistory::class, 'record_id')->orderBy('id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'assigned_supplier_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'requester_unit_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'requester_dept_id');
    }
}
