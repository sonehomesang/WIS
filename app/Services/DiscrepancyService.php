<?php

namespace App\Services;

use App\Models\DiscrepancyAdvice;
use App\Models\Supplier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * DiscrepancyService — state machine ໃບ DA (Phase 6.8b).
 *
 * draft → submitted → purchasing_review → pending_approval → resolved | cancelled
 *   submit          (warehouse) draft → submitted
 *   purchasingStart (approver)  submitted → purchasing_review
 *   purchasingDecide(approver)  purchasing_review → pending_approval
 *   approve         (leader)    pending_approval → resolved (+ next_step oga/finished)
 *   reject          (leader)    pending_approval → purchasing_review
 *   cancel          (warehouse) draft|submitted|purchasing_review → cancelled
 */
class DiscrepancyService
{
    public function nextNumber(int $year): string
    {
        $prefix = "DA{$year}-";
        $max = DB::table('discrepancy_advices')->where('da_number', 'like', $prefix.'%')
            ->lockForUpdate()->max('da_number');
        $seq = $max ? ((int) substr($max, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function createDraft(array $data, $actor): DiscrepancyAdvice
    {
        return DB::transaction(function () use ($data, $actor) {
            $supplier = ! empty($data['supplier_id']) ? Supplier::find($data['supplier_id']) : null;
            $date = Carbon::parse($data['date'] ?? Carbon::today());

            $record = DiscrepancyAdvice::create([
                'da_number' => $this->nextNumber((int) $date->year),
                'date' => $date->toDateString(),
                'po_number' => $data['po_number'] ?? null,
                'po_date' => ! empty($data['po_date']) ? Carbon::parse($data['po_date'])->toDateString() : null,
                'supplier_id' => $supplier?->id,
                'supplier_name' => $supplier?->name ?? ($data['supplier_name'] ?? null),
                'purchasing_officer_name' => $data['purchasing_officer_name'] ?? null,
                'vendor_packing_list' => $data['vendor_packing_list'] ?? null,
                'vendor_invoice_number' => $data['vendor_invoice_number'] ?? null,
                'discrepancy_types' => $data['discrepancy_types'] ?? [],
                'comments' => $data['comments'] ?? null,
                'warehouse_recommendation_kind' => $data['warehouse_recommendation_kind'] ?? null,
                'warehouse_recommendation_text' => $data['warehouse_recommendation_text'] ?? null,
                'raised_by' => $actor->id,
                'raised_by_name' => $actor->display_name ?? $actor->email,
                'status' => 'draft',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            foreach (array_values($data['items'] ?? []) as $i => $it) {
                $record->items()->create([
                    'stock_code' => $it['stock_code'] ?? null,
                    'description' => $it['description'] ?? '—',
                    'qty_ordered' => (int) ($it['qty_ordered'] ?? 0),
                    'qty_delivered' => (int) ($it['qty_delivered'] ?? 0),
                    'qty_received' => (int) ($it['qty_received'] ?? 0),
                    'comments' => $it['comments'] ?? null,
                    'sort_order' => $i,
                ]);
            }

            $this->recordHistory($record, 'create', $actor);

            return $record->refresh();
        });
    }

    /** Replace the line items of a DRAFT discrepancy advice. */
    public function replaceItems(DiscrepancyAdvice $r, array $items, $actor): void
    {
        $this->assert($r->status === 'draft', 'ແກ້ ລາຍການ ໄດ້ສະເພາະ ຮ່າງ (draft).');
        DB::transaction(function () use ($r, $items, $actor) {
            $r->items()->delete();
            foreach (array_values($items) as $i => $it) {
                if (trim($it['description'] ?? '') === '') {
                    continue;
                }
                $r->items()->create([
                    'stock_code' => $it['stock_code'] ?? null,
                    'description' => $it['description'] ?? '—',
                    'qty_ordered' => (int) ($it['qty_ordered'] ?? 0),
                    'qty_delivered' => (int) ($it['qty_delivered'] ?? 0),
                    'qty_received' => (int) ($it['qty_received'] ?? 0),
                    'comments' => $it['comments'] ?? null,
                    'sort_order' => $i,
                ]);
            }
            $r->forceFill(['updated_by' => $actor->id])->save();
            $this->recordHistory($r, 'editItems', $actor);
        });
    }

    public function transition(DiscrepancyAdvice $r, string $action, $actor, array $opts = []): DiscrepancyAdvice
    {
        return DB::transaction(function () use ($r, $action, $actor, $opts) {
            match ($action) {
                'submit' => $this->doSubmit($r),
                'purchasingStart' => $this->doPurchasingStart($r),
                'purchasingDecide' => $this->doPurchasingDecide($r, $actor, $opts),
                'approve' => $this->doApprove($r, $actor, $opts),
                'reject' => $this->doReject($r, $opts),
                'cancel' => $this->doCancel($r, $opts),
                default => throw ValidationException::withMessages(['status' => "Unknown action: {$action}"]),
            };

            $r->updated_by = $actor->id;
            $r->save();
            $this->recordHistory($r, $action, $actor, $opts['comment'] ?? null);
            $this->notify($r, $action, $actor);

            return $r->refresh();
        });
    }

    /** In-app notifications ຕອນ transition ສຳຄັນ (Phase 6.11). */
    private function notify(DiscrepancyAdvice $r, string $action, $actor): void
    {
        $svc = app(NotificationService::class);
        $link = route('da.show', $r);
        $vars = ['number' => $r->da_number, 'actor' => $actor->display_name ?? $actor->email];
        match ($action) {
            'submit' => $svc->notifyRoleTemplate('admin', 'info', 'da.submit', $vars, $link),
            'purchasingDecide' => $svc->notifyRoleTemplate('approver', 'info', 'da.pending', $vars, $link),
            'approve' => $svc->notifyRoleTemplate('warehouse_staff', 'success', 'da.resolved', $vars, $link),
            default => null,
        };
    }

    private function doSubmit(DiscrepancyAdvice $r): void
    {
        $this->assert($r->status === 'draft', 'submit ໄດ້ສະເພາະ draft.');
        $this->assert($r->items()->exists(), 'ຕ້ອງມີຢ່າງໜ້ອຍ 1 ລາຍການ.');
        $r->status = 'submitted';
        $r->raised_at = now();
    }

    private function doPurchasingStart(DiscrepancyAdvice $r): void
    {
        $this->assert($r->status === 'submitted', 'ເລີ່ມ review ໄດ້ສະເພາະ submitted.');
        $r->status = 'purchasing_review';
    }

    private function doPurchasingDecide(DiscrepancyAdvice $r, $actor, array $opts): void
    {
        $this->assert($r->status === 'purchasing_review', 'ຕັດສິນໃຈ ໄດ້ສະເພາະ purchasing_review.');
        $r->status = 'pending_approval';
        $r->purchasing_decisions = $opts['decisions'] ?? [];
        $r->purchasing_note = $opts['note'] ?? null;
        $r->return_transport_account = $opts['transport_account'] ?? null;
        $r->return_transport_mode = $opts['transport_mode'] ?? null;
        $r->return_carrier_name = $opts['carrier_name'] ?? null;
        $r->return_carrier_phone = $opts['carrier_phone'] ?? null;
        $r->purchasing_officer_user_id = $actor->id;
        $r->purchasing_decided_at = now();
    }

    private function doApprove(DiscrepancyAdvice $r, $actor, array $opts): void
    {
        $this->assert($r->status === 'pending_approval', 'approve ໄດ້ສະເພາະ pending_approval.');
        $r->status = 'resolved';
        $r->resolution_action = $opts['resolution'] ?? null;
        $r->approved_title = $opts['title'] ?? null;
        $r->next_step = in_array($opts['next_step'] ?? null, ['oga', 'finished'], true) ? $opts['next_step'] : 'finished';
        $r->approved_by_user_id = $actor->id;
        $r->approved_by_name = $actor->display_name ?? $actor->email;
        $r->approved_at = now();
    }

    private function doReject(DiscrepancyAdvice $r, array $opts): void
    {
        $this->assert($r->status === 'pending_approval', 'reject ໄດ້ສະເພາະ pending_approval.');
        $this->assert(! empty($opts['reason']), 'ຕ້ອງໃສ່ເຫດຜົນ.');
        $r->status = 'purchasing_review';   // loop back
        $r->reject_reason = $opts['reason'];
    }

    private function doCancel(DiscrepancyAdvice $r, array $opts): void
    {
        $this->assert(in_array($r->status, ['draft', 'submitted', 'purchasing_review'], true), 'ຍົກເລີກບໍ່ໄດ້ໃນສະຖານະນີ້.');
        $r->status = 'cancelled';
        $r->cancel_reason = $opts['reason'] ?? null;
    }

    private function recordHistory(DiscrepancyAdvice $r, string $action, $actor, ?string $comment = null): void
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
