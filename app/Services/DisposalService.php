<?php

namespace App\Services;

use App\Models\DisposalRecord;
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
                    'currency' => $it['currency'] ?? null,
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
