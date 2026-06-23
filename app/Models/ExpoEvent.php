<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpoEvent extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_companies_at_expo' => 'integer',
    ];

    public function attendees(): HasMany
    {
        return $this->hasMany(ExpoAttendee::class, 'event_id')->orderBy('id');
    }

    public function companies(): HasMany
    {
        return $this->hasMany(ExpoCompany::class, 'event_id')->orderBy('sort_order')->orderBy('id');
    }
}
