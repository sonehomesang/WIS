<?php

namespace App\Services;

use App\Models\DepositRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * DepositService — state machine ການຝາກເຄື່ອງ (Phase 6.8a).
 *
 * States: draft → submitted → accepted → stored ⇄ needs_fix → claimed | cancelled
 *   submit        (owner)      draft → submitted
 *   accept        (warehouse)  submitted → accepted   (assign storage + instructions)
 *   confirmStored (warehouse)  accepted|needs_fix → stored
 *   flagIssue     (warehouse)  stored → needs_fix
 *   confirmFixed  (warehouse)  needs_fix → stored     (alias of confirmStored)
 *   confirmClaim  (warehouse)  stored → claimed
 *   cancel        (owner)      draft|submitted|accepted → cancelled
 */
class DepositService
{
    /** Counter DP{YYYY}-NNNN (transaction-safe — ເອີ້ນພາຢໃນ DB::transaction). */
    public function nextNumber(int $year): string
    {
        $prefix = "DP{$year}-";
        $max = DB::table('deposit_records')->where('request_number', 'like', $prefix.'%')
            ->lockForUpdate()->max('request_number');
        $seq = $max ? ((int) substr($max, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * ສ້າງ draft ໃໝ່ + items + history.
     *
     * @param  array  $data  request_type, item_category, origin_source, deposit_reason, expected_duration,
     *                       deposit_date, expected_arrival, expected_claim_date, remark,
     *                       items: [{item_name, description?, qty, unit?, estimated_value?, currency?, condition_on_deposit?}]
     */
    public function createDraft(array $data, $actor): DepositRecord
    {
        return DB::transaction(function () use ($data, $actor) {
            $depositDate = Carbon::parse($data['deposit_date'] ?? Carbon::today());

            $record = DepositRecord::create([
                'request_number' => $this->nextNumber((int) $depositDate->year),
                'owner_user_id' => $actor->id,
                'owner_email' => mb_strtolower($actor->email),
                'owner_name' => $actor->display_name ?? $actor->email,
                'owner_unit_id' => $data['owner_unit_id'] ?? $actor->unit_id ?? null,
                'owner_dept_id' => $data['owner_dept_id'] ?? $actor->department_id ?? null,
                'request_type' => $data['request_type'] ?? 'walk_in',
                'item_category' => $data['item_category'] ?? null,
                'origin_source' => $data['origin_source'] ?? null,
                'deposit_reason' => $data['deposit_reason'] ?? null,
                'expected_duration' => $data['expected_duration'] ?? null,
                'deposit_date' => $depositDate->toDateString(),
                'expected_arrival' => ! empty($data['expected_arrival']) ? Carbon::parse($data['expected_arrival'])->toDateString() : null,
                'expected_claim_date' => ! empty($data['expected_claim_date']) ? Carbon::parse($data['expected_claim_date'])->toDateString() : null,
                'remark' => $data['remark'] ?? null,
                'status' => 'draft',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            foreach (array_values($data['items'] ?? []) as $i => $it) {
                $record->items()->create([
                    'item_id' => $it['item_id'] ?? null,
                    'item_name' => $it['item_name'],
                    'description' => $it['description'] ?? null,
                    'qty' => max(1, (int) ($it['qty'] ?? 1)),
                    'unit' => $it['unit'] ?? null,
                    'estimated_value' => ($it['estimated_value'] ?? null) !== null && $it['estimated_value'] !== '' ? $it['estimated_value'] : null,
                    'currency' => $it['currency'] ?? null,
                    'condition_on_deposit' => $it['condition_on_deposit'] ?? null,
                    'sort_order' => $i,
                ]);
            }

            $this->recordHistory($record, 'create', $actor);

            return $record;
        });
    }

    /** ປະຕິບັດ transition. */
    public function transition(DepositRecord $r, string $action, $actor, array $opts = []): DepositRecord
    {
        return DB::transaction(function () use ($r, $action, $actor, $opts) {
            match ($action) {
                'submit' => $this->doSubmit($r),
                'accept' => $this->doAccept($r, $actor, $opts),
                'confirmStored', 'confirmFixed' => $this->doConfirmStored($r, $actor),
                'flagIssue' => $this->doFlagIssue($r, $actor, $opts),
                'confirmClaim' => $this->doConfirmClaim($r, $actor, $opts),
                'cancel' => $this->doCancel($r, $opts),
                default => throw ValidationException::withMessages(['status' => "Unknown action: {$action}"]),
            };

            $r->updated_by = $actor->id;
            $r->save();
            $this->recordHistory($r, $action, $actor, $opts['comment'] ?? null);
            $this->notify($r, $action);

            return $r->refresh();
        });
    }

    /** In-app notifications ຕອນ transition ສຳຄັນ (Phase 6.11). */
    private function notify(DepositRecord $r, string $action): void
    {
        $svc = app(NotificationService::class);
        $link = route('deposit.show', $r);
        $vars = ['number' => $r->request_number, 'owner' => $r->owner_name];
        match ($action) {
            'submit' => $svc->notifyRoleTemplate('warehouse_staff', 'info', 'deposit.submit', $vars, $link),
            'accept' => $svc->notifyTemplate($r->owner_user_id, 'info', 'deposit.accept', $vars, $link),
            'confirmStored', 'confirmFixed' => $svc->notifyTemplate($r->owner_user_id, 'success', 'deposit.stored', $vars, $link),
            'confirmClaim' => $svc->notifyTemplate($r->owner_user_id, 'success', 'deposit.claim', $vars, $link),
            default => null,
        };
    }

    private function doSubmit(DepositRecord $r): void
    {
        $this->assert($r->status === 'draft', 'submit ໄດ້ສະເພາະ draft.');
        $this->assert($r->items()->exists(), 'ຕ້ອງມີຢ່າງໜ້ອຍ 1 ລາຍການ.');
        $r->status = 'submitted';
    }

    private function doAccept(DepositRecord $r, $actor, array $opts): void
    {
        $this->assert($r->status === 'submitted', 'ຮັບຝາກໄດ້ສະເພາະ submitted.');
        $loc = trim((string) ($opts['storage_location'] ?? ''));
        $shelf = trim((string) ($opts['storage_shelf_label'] ?? ''));
        $this->assert($loc !== '' || $shelf !== '', 'ຕ້ອງລະບຸ ບ່ອນເກັບ ຫຼື ປ້າຍຊັ້ນວາງ ຢ່າງໜ້ອຍ 1 ຢ່າງ.');
        $r->status = 'accepted';
        $r->accepted_at = now();
        $r->warehouse_staff_user_id = $actor->id;
        $r->warehouse_staff_name = $actor->display_name ?? $actor->email;
        $r->storage_location = $loc ?: null;
        $r->storage_shelf_label = $shelf ?: null;
        $r->warehouse_instructions = $opts['warehouse_instructions'] ?? null;
    }

    private function doConfirmStored(DepositRecord $r, $actor): void
    {
        $this->assert(in_array($r->status, ['accepted', 'needs_fix'], true), 'ຢືນຢັນເກັບໄດ້ສະເພາະ accepted/needs_fix.');
        $r->status = 'stored';
        $r->stored_at = now();
        $r->stored_by = $actor->id;
        // ເຄລຍ needs_fix ເມື່ອແກ້ແລ້ວ
        $r->needs_fix_reason = null;
    }

    private function doFlagIssue(DepositRecord $r, $actor, array $opts): void
    {
        $this->assert($r->status === 'stored', 'flag ໄດ້ສະເພາະ stored.');
        $this->assert(! empty($opts['reason']), 'ຕ້ອງໃສ່ເຫດຜົນ.');
        $r->status = 'needs_fix';
        $r->needs_fix_reason = $opts['reason'];
        $r->needs_fix_flagged_at = now();
        $r->needs_fix_flagged_by = $actor->id;
    }

    private function doConfirmClaim(DepositRecord $r, $actor, array $opts): void
    {
        $this->assert($r->status === 'stored', 'ຮັບຄືນໄດ້ສະເພາະ stored.');
        $r->status = 'claimed';
        $r->claimed_at = now();
        $r->claimed_by = $actor->id;
        $r->actual_claim_date = $opts['claim_date'] ?? Carbon::today()->toDateString();
    }

    private function doCancel(DepositRecord $r, array $opts): void
    {
        $this->assert(in_array($r->status, ['draft', 'submitted', 'accepted'], true), 'ຍົກເລີກບໍ່ໄດ້ໃນສະຖານະນີ້.');
        $r->status = 'cancelled';
        $r->cancel_reason = $opts['reason'] ?? null;
        $r->cancelled_at = now();
    }

    private function recordHistory(DepositRecord $r, string $action, $actor, ?string $comment = null): void
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
