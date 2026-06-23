<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpoAttendee extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(ExpoEvent::class, 'event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
