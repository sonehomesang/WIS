<?php

namespace App\Services;

use App\Models\Material;
use App\Models\MaterialRequest;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * RequestService — state machine ໃບເບີກວັດສະດຸ (Phase 6.7b).
 *
 * draft → submitted → approved → validated → dispatched → received → completed
 *                                                              ↘ rejected · cancelled
 *   submit   (requester)   + VAT snapshot
 *   approve  (approver)
 *   reject   (approver/warehouse)
 *   validate (warehouse)
 *   dispatch (supplier / warehouse-on-behalf)
 *   confirmReceipt (warehouse/requester)
 *   close    (warehouse)   + invoice + SAP
 *   cancel   (requester)
 */
class RequestService
{
    /** Optional fields ໃນ Request form — admin ເປີດ/ປິດ ຜ່ານ Settings › System. */
    public const FIELD_KEYS = ['supplier', 'currency', 'rooms', 'functions', 'approver', 'request_type', 'wo_e_form'];

    /** @return array<string,bool> field => enabled (default ເປີດໝົດ). */
    public function fieldConfig(): array
    {
        $saved = Setting::get('request', [])['fields'] ?? [];
        $cfg = [];
        foreach (self::FIELD_KEYS as $k) {
            $cfg[$k] = (bool) ($saved[$k] ?? true);
        }

        return $cfg;
    }

    /**
     * ປຽບທຽບລາຄາ supplier ອື່ນໆ ສຳລັບສິນຄ້າດຽວກັນ (ຈັບคู่ ດ້ວຍ material_nbr ກ່ອນ, ບໍ່ມີ → description).
     * Groundwork ສຳລັບ multi-supplier — ຄืน [] ຖ້າມີ supplier ດຽວ.
     *
     * @return array<int,array{material_id:int,supplier_id:?int,supplier_name:string,unit_price:float,currency:string}>
     */
    public function comparePrices(Material $m): array
    {
        $q = Material::query()->with('supplier')->where('is_active', true)->where('id', '!=', $m->id);
        if ($m->material_nbr) {
            $q->where('material_nbr', $m->material_nbr);
        } else {
            $q->where('description', $m->description);
        }

        return $q->limit(10)->get()->map(fn ($o) => [
            'material_id' => $o->id,
            'supplier_id' => $o->supplier_id,
            'supplier_name' => $o->supplier?->name ?? '—',
            'unit_price' => (float) $o->unit_price,
            'currency' => $o->currency ?? 'THB',
        ])->all();
    }

    public function nextNumber(int $year): string
    {
        $prefix = "MR{$year}-";
        $max = DB::table('material_requests')->where('request_number', 'like', $prefix.'%')
            ->lockForUpdate()->max('request_number');
        $seq = $max ? ((int) substr($max, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array  $data  purpose, request_type, wo_e_form, wo_type, location_id, building_id, rooms,
     *                       functions, remark, currency, assigned_supplier_id, approver_user_id,
     *                       items: [{material_id?, description, category?, unit?, quantity, unit_price, currency?}]
     */
    public function createDraft(array $data, $actor): MaterialRequest
    {
        return DB::transaction(function () use ($data, $actor) {
            $approver = ! empty($data['approver_user_id']) ? User::find($data['approver_user_id']) : null;

            $record = MaterialRequest::create([
                'request_number' => $this->nextNumber((int) Carbon::today()->year),
                'requester_user_id' => $actor->id,
                'requester_email' => mb_strtolower($actor->email),
                'requester_name' => $actor->display_name ?? $actor->email,
                'requester_unit_id' => $data['requester_unit_id'] ?? $actor->unit_id ?? null,
                'requester_dept_id' => $data['requester_dept_id'] ?? $actor->department_id ?? null,
                'purpose' => $data['purpose'] ?? null,
                'request_type' => $data['request_type'] ?? null,
                'wo_e_form' => $data['wo_e_form'] ?? null,
                'wo_type' => $data['wo_type'] ?? null,
                'location_id' => $data['location_id'] ?? null,
                'building_id' => $data['building_id'] ?? null,
                'rooms' => $data['rooms'] ?? null,
                'functions' => $data['functions'] ?? null,
                'remark' => $data['remark'] ?? null,
                'currency' => $data['currency'] ?? 'THB',
                'assigned_supplier_id' => $data['assigned_supplier_id'] ?? null,
                'approver_user_id' => $approver?->id,
                'approver_email' => $approver ? mb_strtolower($approver->email) : null,
                'approver_name' => $approver?->display_name ?? $approver?->email,
                'status' => 'draft',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            foreach (array_values($data['items'] ?? []) as $i => $it) {
                $mat = ! empty($it['material_id']) ? Material::find($it['material_id']) : null;
                $record->items()->create([
                    'material_id' => $mat?->id,
                    'material_nbr' => $it['material_nbr'] ?? $mat?->material_nbr,
                    'category' => $it['category'] ?? $mat?->category,
                    'description' => $it['description'] ?? $mat?->description ?? '—',
                    'unit' => $it['unit'] ?? $mat?->unit,
                    'quantity' => max(1, (int) ($it['quantity'] ?? 1)),
                    'unit_price' => isset($it['unit_price']) && $it['unit_price'] !== '' ? (float) $it['unit_price'] : (float) ($mat?->unit_price ?? 0),
                    'currency' => $it['currency'] ?? $mat?->currency ?? ($data['currency'] ?? 'THB'),
                    'shop_prices' => $mat ? ($this->comparePrices($mat) ?: null) : null,
                    'sort_order' => $i,
                ]);
            }

            $this->recalcTotals($record);
            $this->recordHistory($record, 'create', $actor);

            return $record->refresh();
        });
    }

    public function transition(MaterialRequest $r, string $action, $actor, array $opts = []): MaterialRequest
    {
        return DB::transaction(function () use ($r, $action, $actor, $opts) {
            match ($action) {
                'submit' => $this->doSubmit($r),
                'approve' => $this->doApprove($r, $actor),
                'reject' => $this->doReject($r, $opts),
                'validate' => $this->doValidate($r, $actor),
                'dispatch' => $this->doDispatch($r, $actor, $opts),
                'confirmReceipt' => $this->doConfirmReceipt($r, $actor, $opts),
                'close' => $this->doClose($r, $actor, $opts),
                'cancel' => $this->doCancel($r, $opts),
                default => throw ValidationException::withMessages(['status' => "Unknown action: {$action}"]),
            };

            $r->updated_by = $actor->id;
            $r->save();
            $this->recordHistory($r, $action, $actor, $opts['comment'] ?? null);

            return $r->refresh();
        });
    }

    private function doSubmit(MaterialRequest $r): void
    {
        $this->assert($r->status === 'draft', 'submit ໄດ້ສະເພาະ draft.');
        $this->assert($r->items()->exists(), 'ຕ້ອງມີຢ່າງໜ້ອຍ 1 ລາຍການ.');
        $this->snapshotVat($r);   // freeze VAT ຕอนสົ່ງ
        $r->status = 'submitted';
    }

    private function doApprove(MaterialRequest $r, $actor): void
    {
        $this->assert($r->status === 'submitted', 'approve ໄດ້ສະເພາะ submitted.');
        $r->status = 'approved';
        $r->approved_at = now();
        $r->approver_user_id = $actor->id;
        $r->approver_name = $actor->display_name ?? $actor->email;
    }

    private function doReject(MaterialRequest $r, array $opts): void
    {
        $this->assert(in_array($r->status, ['submitted', 'approved'], true), 'reject ໄດ້ສະເພาະ submitted/approved.');
        $this->assert(! empty($opts['reason']), 'ຕ້ອງໃສ່ເຫດผົน.');
        $r->status = 'rejected';
        $r->reject_reason = $opts['reason'];
    }

    private function doValidate(MaterialRequest $r, $actor): void
    {
        $this->assert($r->status === 'approved', 'validate ໄດ້ສະເພาະ approved.');
        $r->status = 'validated';
        $r->validated_at = now();
        $r->warehouse_staff_user_id = $actor->id;
        $r->warehouse_staff_name = $actor->display_name ?? $actor->email;
    }

    private function doDispatch(MaterialRequest $r, $actor, array $opts): void
    {
        $this->assert($r->status === 'validated', 'dispatch ໄດ້ສະເພาະ validated.');
        $r->status = 'dispatched';
        $r->dispatched_at = now();
        $r->dispatched_by = $actor->id;
        $r->delivery_method = $opts['delivery_method'] ?? null;
        $r->planned_delivery_date = $opts['planned_delivery_date'] ?? null;
    }

    private function doConfirmReceipt(MaterialRequest $r, $actor, array $opts): void
    {
        $this->assert($r->status === 'dispatched', 'ຮັບເຄື່ອງ ໄດ້ສະເພาະ dispatched.');
        $r->status = 'received';
        $r->received_at = now();
        $r->received_by = $actor->id;
        $r->received_by_name = $actor->display_name ?? $actor->email;
        $r->invoice_received = (bool) ($opts['invoice_received'] ?? false);
        $r->delivery_note_received = (bool) ($opts['delivery_note_received'] ?? false);
        $r->spec_match = (bool) ($opts['spec_match'] ?? false);
    }

    private function doClose(MaterialRequest $r, $actor, array $opts): void
    {
        $this->assert($r->status === 'received', 'close ໄດ້ສະເພາะ received.');
        $this->assert(! empty($opts['invoice_number']), 'ຕ້ອງໃສ່ເລກ invoice.');
        $this->assert(! empty($opts['sap_reference']), 'ຕ້ອງໃສ່ SAP reference.');
        $r->status = 'completed';
        $r->completed_at = now();
        $r->completed_by = $actor->id;
        $r->invoice_number = $opts['invoice_number'];
        $r->sap_reference = $opts['sap_reference'];
    }

    private function doCancel(MaterialRequest $r, array $opts): void
    {
        $this->assert(in_array($r->status, ['draft', 'submitted', 'approved', 'validated'], true), 'ຍົກເລີກບໍ່ໄດ້ໃນສະຖານະນີ້.');
        $r->status = 'cancelled';
        $r->cancel_reason = $opts['reason'] ?? null;
    }

    /** ຄິດ total ໃໝ່ຈาก items (ບໍ່ແຕະ VAT snapshot). */
    public function recalcTotals(MaterialRequest $r): void
    {
        $total = (float) $r->items()->get()->sum(fn ($it) => (float) $it->unit_price * (int) $it->quantity);
        $r->total = $total;
        $r->vat_amount = $r->vat_enabled ? round($total * ((float) $r->vat_rate) / 100, 2) : 0;
        $r->grand_total = $total + (float) $r->vat_amount;
        $r->save();
    }

    /** Freeze ອັດຕາ VAT ຕอน submit (supplier ກ່ອນ, ບໍ່ມີ → global). */
    private function snapshotVat(MaterialRequest $r): void
    {
        $vat = ['rate' => 0.0, 'enabled' => false];
        if ($r->assigned_supplier_id && ($s = Supplier::find($r->assigned_supplier_id))) {
            $vat = $s->resolveVat();
        } else {
            $g = Setting::get('vat', ['rate' => 10, 'enabled' => true]);
            $vat = ['rate' => (float) ($g['rate'] ?? 10), 'enabled' => (bool) ($g['enabled'] ?? true)];
        }
        $r->vat_rate = $vat['rate'];
        $r->vat_enabled = (bool) $vat['enabled'];
        $total = (float) $r->items()->get()->sum(fn ($it) => (float) $it->unit_price * (int) $it->quantity);
        $r->total = $total;
        $r->vat_amount = $r->vat_enabled ? round($total * (float) $r->vat_rate / 100, 2) : 0;
        $r->grand_total = $total + (float) $r->vat_amount;
    }

    private function recordHistory(MaterialRequest $r, string $action, $actor, ?string $comment = null): void
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
