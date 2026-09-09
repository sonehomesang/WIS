<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserHistory extends Model
{
    public const UPDATED_AT = null; // append-only — created_at ເທົ່ານັ້ນ

    protected $table = 'user_history';

    protected $guarded = ['id'];

    /** The target user this history row is about. */
    public function record(): BelongsTo
    {
        return $this->belongsTo(User::class, 'record_id');
    }
}
