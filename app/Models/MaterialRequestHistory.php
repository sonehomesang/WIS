<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialRequestHistory extends Model
{
    public const UPDATED_AT = null; // append-only

    protected $table = 'material_request_history';

    protected $guarded = ['id'];

    public function record(): BelongsTo
    {
        return $this->belongsTo(MaterialRequest::class, 'record_id');
    }
}
