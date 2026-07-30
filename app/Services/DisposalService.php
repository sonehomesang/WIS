<?php

namespace App\Services;

use App\Models\DepositItem;
use App\Models\DisposalRecord;
use App\Models\Equipment;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * DisposalService — state machine ໃບ ຂໍ ຈຳໜ່າຍ ເຄື່ອງ ຊຳລຸດ (Phase 1: createDraft + submit + cancel).
 *
 * States: draft → committee_review → technical_review → manager_review → executive_review
 *              → approved → disposed | rejected(→draft) | cancelled
 *   submit  (preparer)  draft → committee_review   (stamp preparer sign-off)
 *   cancel  (preparer)  any non-final → cancelled
 *
 * TODO Phase 3: sign (advance committee→technical→manager→executive→approved) + reject(→draft).
 * TODO Phase 6: confirm-then-update source registers (Equipment retired / Inventory off / Deposit disposed) → disposed.
 */
class DisposalService
{
    /** Counter DS{YYYY}-NNNN (transaction-safe — ເອີ້ນ ພາຍໃນ DB::transaction). */
    public function nextNumber(int $year): string
    {
        $prefix = "DS{$year}-";
        $max = DB::table('disposal_records')->where('request_number', 'like', $prefix.'%')
            ->lockForUpdate()->max('request_number');
        $seq = $max ? ((int) substr($max, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * ສ້າງ draft ໃໝ່ + items + history.
     *
     * @param  array  $data  title?, department_id?, note?, items: [{source_type, source_id?, item_name,
     *                       asset_code?, fixed_asset_no?, qty, unit?, condition?, reason?, reason_detail?,
     *                       recommendation?, recommendation_detail?, estimated_value?, currency?, history?[], photos?[]}]
     */
    public function createDraft(array $data, $actor): DisposalRecord
    {
        return DB::transaction(function () use ($data, $actor) {
            $record = DisposalRecord::create([
                'request_number' => $this->nextNumber((int) now()->year),
                'title' => $data['title'] ?? null,
                'department_id' => $data['department_id'] ?? $actor->department_id ?? null,
                'note' => $data['note'] ?? null,
                'prepared_by_user_id' => $actor->id,
                'prepared_by_name' => $actor->display_name ?? $actor->email,
                'status' => 'draft',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            foreach (array_values($data['items'] ?? []) as $i => $it) {
                $record->items()->create([
                    'source_type' => in_array($it['source_type'] ?? 'new', ['inventory', 'equipment', 'deposit', 'new'], true) ? $it['source_type'] : 'new',
                    'source_id' => ($it['source_id'] ?? null) ?: null,
                    'item_name' => $it['item_name'],
                    'asset_code' => ($it['asset_code'] ?? null) ?: null,
                    'fixed_asset_no' => ($it['fixed_asset_no'] ?? null) ?: null,
                    'qty' => max(1, (int) ($it['qty'] ?? 1)),
                    'unit' => $it['unit'] ?? null,
                    'condition' => $it['condition'] ?? null,
                    'reason' => ($it['reason'] ?? null) ?: null,
                    'reason_detail' => ($it['reason_detail'] ?? null) ?: null,
                    'recommendation' => ($it['recommendation'] ?? null) ?: null,
                    'recommendation_detail' => ($it['recommendation_detail'] ?? null) ?: null,
                    'estimated_value' => ($it['estimated_value'] ?? null) !== null && $it['estimated_value'] !== '' ? $it['estimated_value'] : null,
                    'currency' => in_array($it['currency'] ?? null, ['LAK', 'THB', 'USD'], true) ? $it['currency'] : null,
                    'history' => ! empty($it['history']) ? array_values($it['history']) : null,
                    'photos' => ! empty($it['photos']) ? array_values($it['photos']) : null,
                    'sort_order' => $i,
                ]);
            }

            $this->recordHistory($record, 'create', $actor);

            return $record;
        });
    }

    /** ປະຕິບັດ transition. */
    public function transition(DisposalRecord $r, string $action, $actor, array $opts = []): DisposalRecord
    {
        return DB::transaction(function () use ($r, $action, $actor, $opts) {
            match ($action) {
                'submit' => $this->doSubmit($r, $actor),
                'sign' => $this->doSign($r, $actor, $opts),
                'reject' => $this->doReject($r, $actor, $opts),
                'dispose' => $this->doDispose($r, $actor, $opts),
                'cancel' => $this->doCancel($r, $opts),
                default => throw ValidationException::withMessages(['status' => "Unknown action: {$action}"]),
            };

            $r->updated_by = $actor->id;
            $r->save();
            $this->recordHistory($r, $action, $actor, $opts['comment'] ?? null);

            return $r->refresh();
        });
    }

    private function doSubmit(DisposalRecord $r, $actor): void
    {
        $this->assert($r->status === 'draft', 'submit ໄດ້ ສະເພາະ draft.');
        $this->assert($r->items()->exists(), 'ຕ້ອງ ມີ ຢ່າງໜ້ອຍ 1 ລາຍການ.');
        $r->status = 'committee_review';
        $r->prepared_at = now();
        $r->reject_reason = null;

        // ຜູ້ ເຮັດລິສ ເຊັນ ຕອນ ສົ່ງ (ຂັ້ນ 1).
        $r->signoffs()->create([
            'role_key' => 'preparer',
            'stage_order' => 1,
            'user_id' => $actor->id,
            'name' => $actor->display_name ?? $actor->email,
            'decision' => 'approved',
            'signed_at' => now(),
        ]);
    }

    /** ເຊັນ ຮັບຮອງ ຂັ້ນ ປັດຈຸບັນ → ຂຶ້ນ ຂັ້ນ ຕໍ່ໄປ. ຄະນະ = ໃສ່ ຫຼາຍ ຄົນ (opts['committee']=[{name,title}]). */
    private function doSign(DisposalRecord $r, $actor, array $opts): void
    {
        $stage = $r->currentStageKey();
        $this->assert($stage !== null, 'ບໍ່ ຢູ່ ໃນ ຂັ້ນ ເຊັນ.');

        if ($stage === 'committee') {
            $members = array_values(array_filter($opts['committee'] ?? [], fn ($m) => trim((string) ($m['name'] ?? '')) !== ''));
            $this->assert(count($members) >= 1, 'ຕ້ອງ ມີ ຄະນະກຳມະການ ຢ່າງໜ້ອຍ 1 ຄົນ.');
            foreach ($members as $mem) {
                $r->signoffs()->create([
                    'role_key' => 'committee', 'stage_order' => 2,
                    'name' => $mem['name'], 'title' => $mem['title'] ?? null,
                    'decision' => 'approved', 'signed_at' => now(), 'comment' => $opts['comment'] ?? null,
                ]);
            }
            $r->status = 'technical_review';

            return;
        }

        $map = [
            'technical' => ['order' => 3, 'next' => 'manager_review'],
            'manager' => ['order' => 4, 'next' => 'executive_review'],
            'executive' => ['order' => 5, 'next' => 'approved'],
        ];
        $cfg = $map[$stage];
        $r->signoffs()->create([
            'role_key' => $stage, 'stage_order' => $cfg['order'],
            'user_id' => $actor->id, 'name' => $actor->display_name ?? $actor->email, 'title' => $opts['title'] ?? null,
            'decision' => 'approved', 'signed_at' => now(), 'comment' => $opts['comment'] ?? null,
        ]);
        $r->status = $cfg['next'];
    }

    /** ຕີ ກັບ → draft ພ້ອມ ເຫດຜົນ. */
    private function doReject(DisposalRecord $r, $actor, array $opts): void
    {
        $stage = $r->currentStageKey();
        $this->assert($stage !== null, 'ຕີ ກັບ ບໍ່ ໄດ້ ໃນ ສະຖານະ ນີ້.');
        $this->assert(! empty($opts['reason']), 'ຕ້ອງ ໃສ່ ເຫດຜົນ ຕີ ກັບ.');
        $r->signoffs()->create([
            'role_key' => $stage, 'stage_order' => 0,
            'user_id' => $actor->id, 'name' => $actor->display_name ?? $actor->email,
            'decision' => 'rejected', 'signed_at' => now(), 'comment' => $opts['reason'],
        ]);
        $r->status = 'draft';
        $r->reject_reason = $opts['reason'];
    }

    /** ອະນຸມັດ ສຳເລັດ → ຈຳໜ່າຍ; ຖ້າ update_registers=true → ອັບເດດ ທະບຽນ ຕົ້ນທາງ (Phase 6). */
    private function doDispose(DisposalRecord $r, $actor, array $opts): void
    {
        $this->assert($r->status === 'approved', 'ຈຳໜ່າຍ ໄດ້ ສະເພາະ ໃບ ທີ່ ອະນຸມັດ ແລ້ວ.');
        if ($opts['update_registers'] ?? false) {
            $this->updateSourceRegisters($r);
            $r->registers_updated_at = now();
            $r->registers_updated_by = $actor->id;
        }
        $r->status = 'disposed';
    }

    /** ອັບເດດ ທະບຽນ ຕົ້ນທາງ: Equipment → retired · Inventory → ปิด · Deposit → disposed. */
    protected function updateSourceRegisters(DisposalRecord $r): void
    {
        foreach ($r->items()->get() as $it) {
            if (! $it->source_id) {
                continue;
            }
            if ($it->source_type === 'equipment' && ($e = Equipment::find($it->source_id))) {
                $b = $e->statusBreakdown();
                $retire = min(max(1, (int) $it->qty), $b['active']);
                $e->update(['status_counts' => ['active' => $b['active'] - $retire, 'repair' => $b['repair'], 'retired' => $b['retired'] + $retire]]);
            } elseif ($it->source_type === 'inventory' && ($inv = InventoryItem::find($it->source_id))) {
                $inv->update(['is_active' => false]);
            } elseif ($it->source_type === 'deposit' && ($di = DepositItem::find($it->source_id))) {
                $di->update(['condition_on_claim' => 'ຈຳໜ່າຍ (disposed) · '.$r->request_number]);
            }
        }
    }

    private function doCancel(DisposalRecord $r, array $opts): void
    {
        $this->assert(! in_array($r->status, ['disposed', 'cancelled'], true), 'ຍົກເລີກ ບໍ່ ໄດ້ ໃນ ສະຖານະ ນີ້.');
        $r->status = 'cancelled';
        $r->cancel_reason = $opts['reason'] ?? null;
        $r->cancelled_at = now();
    }

    private function recordHistory(DisposalRecord $r, string $action, $actor, ?string $comment = null): void
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
