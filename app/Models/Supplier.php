<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug', 'name', 'name_en', 'contact_person', 'contact_phone', 'contact_email',
        'address', 'tax_id', 'payment_terms', 'default_currency', 'notes',
        'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = ['is_active' => 'boolean'];
}
