<?php

namespace App\Observers;

use App\Models\Material;
use Illuminate\Support\Carbon;

/**
 * ບັນທຶກປະຫວັດລາຄາ ອັດຕະໂນมัด ເມื່อ unit_price ປ່ຽນ (ຫຼື ຕอนสร้างถ้ามีลาคา),
 * ພ້ອມ ວັນທີ + ເລກສັນຍາ + ໃຜແກ້. ບໍ່ກະທົບ record ເກົ່າ (append-only history).
 */
class MaterialObserver
{
    public function created(Material $m): void
    {
        if ($m->unit_price !== null) {
            $this->logPrice($m);
            // last_price_update ໂດຍບໍ່ trigger updated() ຊ້ຳ
            $m->updateQuietly(['last_price_update' => Carbon::today()->toDateString()]);
        }
    }

    public function updating(Material $m): void
    {
        if ($m->isDirty('unit_price')) {
            $m->last_price_update = Carbon::today()->toDateString();
        }
    }

    public function updated(Material $m): void
    {
        if ($m->wasChanged('unit_price')) {
            $this->logPrice($m);
        }
    }

    private function logPrice(Material $m): void
    {
        $m->priceHistory()->create([
            'unit_price' => $m->unit_price,
            'currency' => $m->currency,
            'contract_number' => $m->contract_number,
            'contract_date' => $m->contract_date?->toDateString(),
            'update_date' => Carbon::today()->toDateString(),
            'updated_by' => auth()->id(),
        ]);
    }
}
