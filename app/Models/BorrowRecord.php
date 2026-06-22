<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class BorrowRecord extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'requires_acknowledge' => 'boolean',
        'period_days' => 'integer',
        'borrow_date' => 'date',
        'planned_return_date' => 'date',
        'actual_return_date' => 'date',
        'acknowledged_at' => 'datetime',
        'approved_at' => 'datetime',
        'taken_at' => 'datetime',
        'returned_at' => 'datetime',
        'borrower_return_date' => 'date',
        'extension_proposed_date' => 'date',
        'extension_requested_at' => 'datetime',
        'extension_decided_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(BorrowItem::class, 'record_id')->orderBy('sort_order')->orderBy('id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(BorrowHistory::class, 'record_id')->orderBy('id');
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'borrower_user_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'borrower_unit_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'borrower_dept_id');
    }

    /** ມື້ເຫຼືອ/ເກີນ ສຳລັບ badge "In Xd" / "Overdue Xd". */
    public function getDaysLeftAttribute(): ?int
    {
        if (! in_array($this->status, ['active', 'overdue'], true) || ! $this->planned_return_date) {
            return null;
        }

        return Carbon::today()->diffInDays($this->planned_return_date, false);
    }

    /** overdue = active + ກາຍ planned_return_date (flag ຕอน query, ບໍ່ບັນທຶກ DB). */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'active'
            && $this->planned_return_date !== null
            && $this->planned_return_date->isBefore(Carbon::today());
    }

    /** status ສຳລັບສະແດງ (active + overdue → 'overdue'). */
    public function getDisplayStatusAttribute(): string
    {
        return $this->is_overdue ? 'overdue' : $this->status;
    }
}
