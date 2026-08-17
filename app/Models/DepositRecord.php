<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DepositRecord extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'deposit_date' => 'date',
        'original_deposit_date' => 'date',
        'expected_arrival' => 'date',
        'expected_claim_date' => 'date',
        'actual_claim_date' => 'date',
        'accepted_at' => 'datetime',
        'stored_at' => 'datetime',
        'needs_fix_flagged_at' => 'datetime',
        'claimed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(DepositItem::class, 'record_id')->orderBy('sort_order')->orderBy('id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(DepositHistory::class, 'record_id')->orderBy('id');
    }

    /** ຮ່າງ ທີ່ ໜ້າງານ ບັນທຶກ ແຕ່ ຫົວໜ້າ ຍັງ ບໍ່ ໄດ້ ຕື່ມ ຂໍ້ມູນ ທົ່ວໄປ (ຂັ້ນ 2). */
    public function needsOfficeInfo(): bool
    {
        return $this->status === 'draft' && blank($this->item_category);
    }

    public function scopeNeedsOfficeInfo($query)
    {
        return $query->where('status', 'draft')
            ->where(fn ($w) => $w->whereNull('item_category')->orWhere('item_category', ''));
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'owner_unit_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'owner_dept_id');
    }
}
