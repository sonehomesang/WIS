<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Building extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'location_id', 'slug', 'name', 'name_en', 'code', 'type', 'description', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
