<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnsiApplication extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'app_date' => 'date',
        'submitted_at' => 'datetime',
        'endorsed_at' => 'datetime',
        'approved_at' => 'datetime',
        'warehoused_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public const STATUS_LABELS = [
        'draft' => 'Draft',
        'pending_hos' => 'Pending HoS / TL',
        'pending_manager' => 'Pending Manager',
        'pending_warehouse' => 'Pending Warehouse',
        'completed' => 'Completed',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ];

    /** Which pending status maps to which named approver (assignment-based gate). */
    public const STAGE_APPROVER = [
        'pending_hos' => 'hos_user_id',
        'pending_manager' => 'manager_user_id',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(AnsiItem::class, 'application_id')->orderBy('sort_order')->orderBy('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AnsiAttachment::class, 'application_id')->orderBy('id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(AnsiHistory::class, 'application_id')->orderBy('id');
    }

    public function originator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'originator_user_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'owner_unit_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'owner_dept_id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /** The user whose action is currently required (null once finished). */
    public function pendingApproverId(): ?int
    {
        return match ($this->status) {
            'pending_hos' => $this->hos_user_id,
            'pending_manager' => $this->manager_user_id,
            default => null,
        };
    }
}
