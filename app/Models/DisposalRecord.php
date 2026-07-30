<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** ໃບ ຂໍ ຈຳໜ່າຍ ເຄື່ອງ ຊຳລຸດ (Disposal). */
class DisposalRecord extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'prepared_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'registers_updated_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(DisposalItem::class, 'record_id')->orderBy('sort_order')->orderBy('id');
    }

    public function signoffs(): HasMany
    {
        return $this->hasMany(DisposalSignoff::class, 'record_id')->orderBy('stage_order')->orderBy('id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(DisposalHistory::class, 'record_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_user_id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
