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
        'location_id', 'building_type_id', 'slug', 'name', 'name_en', 'code', 'description', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function buildingType(): BelongsTo
    {
        return $this->belongsTo(BuildingType::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
