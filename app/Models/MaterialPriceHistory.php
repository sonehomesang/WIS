<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialPriceHistory extends Model
{
    public $timestamps = false;

    protected $table = 'material_price_history';

    protected $guarded = ['id'];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'contract_date' => 'date',
        'update_date' => 'date',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
