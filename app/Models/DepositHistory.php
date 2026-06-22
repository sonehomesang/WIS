<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepositHistory extends Model
{
    public const UPDATED_AT = null; // append-only — created_at ເທົ່ານັ້ນ

    protected $table = 'deposit_history';

    protected $guarded = ['id'];

    public function record(): BelongsTo
    {
        return $this->belongsTo(DepositRecord::class, 'record_id');
    }
}
