<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BuildingType extends Model
{
    use SoftDeletes;

    protected $fillable = ['slug', 'name', 'is_active', 'created_by', 'updated_by'];

    protected $casts = ['is_active' => 'boolean'];

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
