<?php

namespace App\Models;

use App\Support\ConditionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepositItem extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'qty' => 'integer',
        'estimated_value' => 'decimal:2',
        'sort_order' => 'integer',
        'condition_set_at' => 'datetime',
    ];

    /** Deposit items whose lifecycle status makes them eligible for disposal. */
    public function scopeDisposable($query)
    {
        return $query->whereIn('condition_status', ConditionStatus::disposable());
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(DepositRecord::class, 'record_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(DepositItemPhoto::class, 'deposit_item_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * path ຮູບ ຮຽງ ໃຫ້ ຮູບ ນຳ ຂອງ ແຕ່ ລະ ມູມ (overall → id → damage) ມາ ກ່ອນ, ຕໍ່ ດ້ວຍ ຮູບ ທີ່ ເຫຼືອ.
     * ໃຊ້ ຕອນ ດຶງ ເຂົ້າ Disposal ເພື່ອ ໃຫ້ ຕຳແໜ່ງ [0][1][2] ຕໍ່ ①Overall ②ID ③Damage ຂອງ PDF ໂປຣຟາຍ ພໍດີ.
     *
     * @return array<int,string>
     */
    public function orderedPhotoPaths(): array
    {
        $bySlot = $this->photos->groupBy('slot');
        $lead = collect(['overall', 'id', 'damage'])
            ->map(fn ($s) => optional($bySlot->get($s))->first())
            ->filter();
        $leadIds = $lead->pluck('id');
        $rest = $this->photos->reject(fn ($p) => $leadIds->contains($p->id));

        return $lead->concat($rest)->pluck('path')->filter()->values()->all();
    }
}
