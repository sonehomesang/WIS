<?php

namespace App\Services;

use App\Models\BorrowRecord;
use App\Models\InventoryItem;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * BorrowService — state machine ການຍືມເຄື່ອງ (Phase 6.6).
 *
 * Workflow steps ເປີດ/ປິດ ໂດຍ admin ຜ່ານ settings 'workflow.borrow':
 *   acknowledge: 'off' | 'optional' | 'required'   (optional = ຂຶ້ນກັບ record.requires_acknowledge)
 *   approve:     'required' | 'off'
 *
 * States: draft → acknowledged → approved → active → returned (+ cancelled · overdue=flag)
 */
class BorrowService
{
    public const WORKFLOW_DEFAULT = ['acknowledge' => 'optional', 'approve' => 'required'];

    /** @return array{acknowledge:string, approve:string} */
    public function workflowConfig(): array
    {
        $wf = Setting::get('workflow', []);

        return array_merge(self::WORKFLOW_DEFAULT, $wf['borrow'] ?? []);
    }

    /** ຂັ້ນຕອນ active ສຳລັບ record ນີ້ (ຕາມ config + per-record choice). */
    public function effectiveSteps(BorrowRecord $r): array
    {
        $c = $this->workflowConfig();
        $ack = $c['acknowledge'] === 'required'
            || ($c['acknowledge'] === 'optional' && $r->requires_acknowledge);

        return ['acknowledge' => $ack, 'approve' => $c['approve'] === 'required'];
    }

    /** Counter BR{YYYY}-NNNN (transaction-safe — ເອີ້ນພายใน DB::transaction). */
    public function nextNumber(int $year): string
    {
        $prefix = "BR{$year}-";
        $max = DB::table('borrow_records')->where('request_number', 'like', $prefix.'%')
            ->lockForUpdate()->max('request_number');
        $seq = $max ? ((int) substr($max, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * ສ້າງ draft ໃໝ່ + items + history.
     *
     * @param  array  $data  borrow_type, purpose, remark, others_detail, borrow_date, period_days,
     *                       requires_acknowledge, acknowledge_email/name, approver_email/name,
     *                       items: [{item_id?, item_name, qty}]
     */
    public function createDraft(array $data, $actor): BorrowRecord
    {
        return DB::transaction(function () use ($data, $actor) {
            $borrowDate = Carbon::parse($data['borrow_date'] ?? Carbon::today());
            $period = max(1, (int) ($data['period_days'] ?? 7));

            $record = BorrowRecord::create([
                'request_number' => $this->nextNumber((int) $borrowDate->year),
                'borrower_user_id' => $actor->id,
                'borrower_email' => mb_strtolower($actor->email),
                'borrower_name' => $actor->display_name ?? $actor->email,
                'borrower_unit_id' => $data['borrower_unit_id'] ?? $actor->unit_id ?? null,
                'borrower_dept_id' => $data['borrower_dept_id'] ?? $actor->department_id ?? null,
                'borrow_type' => $data['borrow_type'] ?? 'new_inventory',
                'deposit_record_id' => $data['deposit_record_id'] ?? null,
                'others_detail' => $data['others_detail'] ?? null,
                'purpose' => $data['purpose'] ?? null,
                'remark' => $data['remark'] ?? null,
                'borrow_date' => $borrowDate->toDateString(),
                'period_days' => $period,
                'planned_return_date' => $borrowDate->copy()->addDays($period)->toDateString(),
                'requires_acknowledge' => (bool) ($data['requires_acknowledge'] ?? false),
                'acknowledge_email' => isset($data['acknowledge_email']) ? mb_strtolower($data['acknowledge_email']) : null,
                'acknowledge_name' => $data['acknowledge_name'] ?? null,
                'approver_email' => isset($data['approver_email']) ? mb_strtolower($data['approver_email']) : null,
                'approver_name' => $data['approver_name'] ?? null,
                'status' => 'draft',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            foreach (array_values($data['items'] ?? []) as $i => $it) {
                $record->items()->create([
                    'item_id' => $it['item_id'] ?? null,
                    'item_name' => $it['item_name'],
                    'qty' => max(1, (int) ($it['qty'] ?? 1)),
                    'sort_order' => $i,
                ]);
            }

            $this->recordHistory($record, 'create', $actor);

            return $record;
        });
    }

    /** ປະຕິບັດ transition. $action: submit|acknowledge|approve|reject|cancel|confirmTake|confirmReturn. */
    public function transition(BorrowRecord $r, string $action, $actor, array $opts = []): BorrowRecord
    {
        return DB::transaction(function () use ($r, $action, $actor, $opts) {
            $steps = $this->effectiveSteps($r);

            match ($action) {
                'submit' => $this->doSubmit($r, $steps),
                'acknowledge' => $this->doAcknowledge($r, $steps, $actor),
                'approve' => $this->doApprove($r, $steps, $actor),
                'confirmTake' => $this->doConfirmTake($r, $actor),
                'confirmReturn' => $this->doConfirmReturn($r, $actor, $opts),
                'cancel', 'reject' => $this->doCancel($r, $opts),
                default => throw ValidationException::withMessages(['action' => "Unknown action: {$action}"]),
            };

            $r->updated_by = $actor->id;
            $r->save();
            $this->recordHistory($r, $action, $actor, $opts['comment'] ?? null);

            return $r->refresh();
        });
    }

    private function doSubmit(BorrowRecord $r, array $steps): void
    {
        $this->assert($r->status === 'draft', 'submit ໄດ້ສະເພາະ draft.');
        $r->status = ($steps['acknowledge'] || $steps['approve']) ? 'acknowledged' : 'approved';
    }

    private function doAcknowledge(BorrowRecord $r, array $steps, $actor): void
    {
        $this->assert($steps['acknowledge'], 'workflow ນີ້ບໍ່ມີຂັ້ນ acknowledge.');
        $this->assert($r->status === 'acknowledged' && $r->acknowledged_at === null, 'acknowledge ບໍ່ໄດ້ໃນສະຖານະນີ້.');
        $r->acknowledged_at = now();
        $r->acknowledge_user_id = $actor->id;
        $r->acknowledge_name = $actor->display_name ?? $actor->email;
        // ຖ້າບໍ່ມີຂັ້ນ approve → acknowledge ດັນໄປ approved ເລີຍ
        if (! $steps['approve']) {
            $r->status = 'approved';
            $r->approved_at = now();
        }
    }

    private function doApprove(BorrowRecord $r, array $steps, $actor): void
    {
        $this->assert($steps['approve'], 'workflow ນີ້ບໍ່ມີຂັ້ນ approve.');
        $this->assert($r->status === 'acknowledged', 'approve ໄດ້ສະເພາະ acknowledged.');
        $this->assert(! $steps['acknowledge'] || $r->acknowledged_at !== null, 'ຕ້ອງ acknowledge ກ່ອນ approve.');
        $r->status = 'approved';
        $r->approved_at = now();
        $r->approver_user_id = $actor->id;
        $r->approver_name = $actor->display_name ?? $actor->email;
    }

    private function doConfirmTake(BorrowRecord $r, $actor): void
    {
        $this->assert($r->status === 'approved', 'confirmTake ໄດ້ສະເພາະ approved.');
        if ($r->borrow_type === 'new_inventory') {
            foreach ($r->items as $it) {
                if ($it->item_id) {
                    InventoryItem::whereKey($it->item_id)->decrement('quantity', $it->qty);
                }
            }
        }
        $r->status = 'active';
        $r->taken_at = now();
        $r->warehouse_staff_user_id = $actor->id;
        $r->warehouse_staff_name = $actor->display_name ?? $actor->email;
    }

    private function doConfirmReturn(BorrowRecord $r, $actor, array $opts): void
    {
        $this->assert(in_array($r->status, ['active', 'overdue'], true), 'confirmReturn ໄດ້ສະເພาະ active.');
        $returnQtys = $opts['return_qty'] ?? []; // [borrow_item_id => qty]
        if ($r->borrow_type === 'new_inventory') {
            foreach ($r->items as $it) {
                $qty = (int) ($returnQtys[$it->id] ?? $it->qty);
                $it->return_qty = $qty;
                $it->save();
                if ($it->item_id && $qty > 0) {
                    InventoryItem::whereKey($it->item_id)->increment('quantity', $qty);
                }
            }
        }
        $r->status = 'returned';
        $r->returned_at = now();
        $r->actual_return_date = Carbon::today()->toDateString();
        $r->warehouse_staff_user_id = $actor->id;
        $r->warehouse_staff_name = $actor->display_name ?? $actor->email;
    }

    private function doCancel(BorrowRecord $r, array $opts): void
    {
        $this->assert(in_array($r->status, ['draft', 'acknowledged', 'approved'], true), 'ຍົກເລີກບໍ່ໄດ້ໃນສະຖານະນີ້.');
        $r->status = 'cancelled';
        $r->cancel_reason = $opts['reason'] ?? null;
    }

    private function recordHistory(BorrowRecord $r, string $action, $actor, ?string $comment = null): void
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
