<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Uom extends Model
{
    use SoftDeletes;

    protected $table = 'uoms';

    protected $fillable = ['slug', 'name', 'name_en', 'is_active', 'created_by', 'updated_by'];

    protected $casts = ['is_active' => 'boolean'];
}
