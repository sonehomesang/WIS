<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AnsiAttachment extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['size' => 'integer'];

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
