<?php

namespace App\Services;

use App\Models\DiscrepancyAdvice;
use App\Models\OutwardsGoodsAdvice;
use App\Models\Supplier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * OgaService — state machine ໃບສົ່ງເຄື່ອງອອກ (Phase 6.8c, warehouse-only).
 *
 * draft → dispatched → delivered | returned ; cancel ຈาก draft
 *   confirmDispatch (warehouse) draft → dispatched (+ driver, truck)
 *   confirmDelivery (warehouse) dispatched → delivered
 *   returnRejected  (warehouse) dispatched → returned (+ reason)
 *   cancel          (warehouse) draft → cancelled
 */
class OgaService
{
    public function nextNumber(int $year): string
    {
        $prefix = "OGA{$year}-";
        $max = DB::table('outwards_goods_advices')->where('oga_number', 'like', $prefix.'%')
            ->lockForUpdate()->max('oga_number');
        $seq = $max ? ((int) substr($max, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function createDraft(array $data, $actor): OutwardsGoodsAdvice
    {
        return DB::transaction(function () use ($data, $actor) {
            $supplier = ! empty($data['supplier_id']) ? Supplier::find($data['supplier_id']) : null;
            $sourceDa = ! empty($data['source_da_id']) ? DiscrepancyAdvice::find($data['source_da_id']) : null;
            $date = Carbon::parse($data['date'] ?? Carbon::today());

            $record = OutwardsGoodsAdvice::create([
                'oga_number' => $this->nextNumber((int) $date->year),
                'date' => $date->toDateString(),
                'po_number' => $data['po_number'] ?? null,
                'ship_via' => $data['ship_via'] ?? null,
                'source_type' => $sourceDa ? 'da' : ($data['source_type'] ?? 'oga'),
                'source_da_id' => $sourceDa?->id,
                'source_da_number' => $sourceDa?->da_number,
                'supplier_id' => $supplier?->id,
                'dispatch_to_name' => $data['dispatch_to_name'] ?? $supplier?->name,
                'dispatch_to_address' => $data['dispatch_to_address'] ?? $supplier?->address,
                'dispatch_to_phone' => $data['dispatch_to_phone'] ?? $supplier?->contact_phone,
                'dispatch_to_contact_person' => $data['dispatch_to_contact_person'] ?? $supplier?->contact_person,
                'dispatch_to_email' => $data['dispatch_to_email'] ?? $supplier?->contact_email,
                'goods_consigned' => $data['goods_consigned'] ?? null,
                'dimension' => $data['dimension'] ?? null,
                'gross_weight_kg' => $data['gross_weight_kg'] ?? null,
                'reason_of_despatch' => $data['reason_of_despatch'] ?? null,
                'comments' => $data['comments'] ?? null,
                'driver_name' => $data['driver_name'] ?? null,
                'truck_plate_number' => $data['truck_plate_number'] ?? null,
                'consign_by_user_id' => $actor->id,
                'consign_by_name' => $actor->display_name ?? $actor->email,
                'status' => 'draft',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            foreach (array_values($data['items'] ?? []) as $i => $it) {
                $qty = max(1, (int) ($it['qty'] ?? 1));
                $uw = ($it['unit_weight_kg'] ?? null) !== null && $it['unit_weight_kg'] !== '' ? (float) $it['unit_weight_kg'] : null;
                $record->items()->create([
                    'description' => $it['description'] ?? '—',
                    'unit' => $it['unit'] ?? null,
                    'qty' => $qty,
                    'unit_weight_kg' => $uw,
                    'total_weight_kg' => $uw !== null ? $uw * $qty : null,
                    'sort_order' => $i,
                ]);
            }

            $this->recalcWeight($record);
            $this->recordHistory($record, 'create', $actor);

            return $record->refresh();
        });
    }

    public function transition(OutwardsGoodsAdvice $r, string $action, $actor, array $opts = []): OutwardsGoodsAdvice
    {
        return DB::transaction(function () use ($r, $action, $actor, $opts) {
            match ($action) {
                'confirmDispatch' => $this->doDispatch($r, $actor, $opts),
                'confirmDelivery' => $this->doDelivery($r, $actor),
                'returnRejected' => $this->doReturn($r, $opts),
                'cancel' => $this->doCancel($r, $opts),
                default => throw ValidationException::withMessages(['status' => "Unknown action: {$action}"]),
            };

            $r->updated_by = $actor->id;
            $r->save();
            $this->recordHistory($r, $action, $actor, $opts['comment'] ?? null);

            return $r->refresh();
        });
    }

    private function doDispatch(OutwardsGoodsAdvice $r, $actor, array $opts): void
    {
        $this->assert($r->status === 'draft', 'dispatch ໄດ້ສະເພาະ draft.');
        if (! empty($opts['driver_name'])) {
            $r->driver_name = $opts['driver_name'];
        }
        if (! empty($opts['truck_plate_number'])) {
            $r->truck_plate_number = $opts['truck_plate_number'];
        }
        $r->status = 'dispatched';
        $r->authorized_by_user_id = $actor->id;
        $r->authorized_by_name = $actor->display_name ?? $actor->email;
        $r->authorized_at = now();
    }

    private function doDelivery(OutwardsGoodsAdvice $r, $actor): void
    {
        $this->assert($r->status === 'dispatched', 'delivery ໄດ້ສະເພາะ dispatched.');
        $r->status = 'delivered';
        $r->completed_by_user_id = $actor->id;
        $r->completed_by_name = $actor->display_name ?? $actor->email;
        $r->completed_at = now();
    }

    private function doReturn(OutwardsGoodsAdvice $r, array $opts): void
    {
        $this->assert($r->status === 'dispatched', 'return ໄດ້ສະເພาະ dispatched.');
        $this->assert(! empty($opts['reason']), 'ຕ້ອງໃສ່ເຫດผົน.');
        $r->status = 'returned';
        $r->reject_reason = $opts['reason'];
    }

    private function doCancel(OutwardsGoodsAdvice $r, array $opts): void
    {
        $this->assert($r->status === 'draft', 'ຍົກເລີກໄດ້ສະເພາະ draft.');
        $r->status = 'cancelled';
        $r->cancel_reason = $opts['reason'] ?? null;
    }

    public function recalcWeight(OutwardsGoodsAdvice $r): void
    {
        $sum = (float) $r->items()->get()->sum(fn ($it) => (float) ($it->total_weight_kg ?? 0));
        $r->total_weight_kg = $sum ?: null;
        $r->save();
    }

    private function recordHistory(OutwardsGoodsAdvice $r, string $action, $actor, ?string $comment = null): void
    {
        $r->history()->create([
            'action' => $action,
            'status' => $r->status,
            'user_id' => $actor->id ?? null,
            'user_name' => $actor->display_name ?? $actor->email ?? null,
            'role' => method_exists($actor, 'getRoleNames') ? ($actor->getRoleNames()->first() ?? null) : null,
            'comment' => $comment,
            'created_at' => now(),
        ]);
    }

    private function assert(bool $ok, string $message): void
    {
        if (! $ok) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }
}
