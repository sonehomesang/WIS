<?php

namespace App\Services;

use App\Models\DepositItem;
use App\Models\DepositRecord;
use App\Models\DisposalRecord;
use App\Models\DisposalSignoff;
use App\Models\Equipment;
use App\Models\InventoryItem;
use App\Models\User;
use App\Notifications\DisposalEndorsementRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * DisposalService — state machine ໃບ ຂໍ ຈຳໜ່າຍ ເຄື່ອງ ຊຳລຸດ.
 *
 * ຮູບ ແບບ ຮັບຮອງ = "ມອບໝາຍ ຄົນ → ເຮັດ ອິດສະລະ" (parallel, ບໍ່ ຈຳກັດ ລຳດັບ):
 * States: draft → in_review → approved → disposed | rejected/draft | cancelled
 *   assignEndorsers  ມອບໝາຍ ຜູ້ ຮັບຮອງ ຕໍ່ 5 ບົດບາດ (draft)
 *   submit           draft → in_review (ຣີເຊັດ ລາຍເຊັນ; ຕ້ອງ ມີ ຜູ້ ຮັບຮອງ ≥1)
 *   endorse          ຜູ້ ຖືກ ມອບໝາຍ ເຊັນ ຊ່ອງ ຕົນ (ໃດ ກ່ອນ-ຫຼັງ ກໍ ໄດ້) → ຄົບ ທຸກ ຄົນ = approved
 *   rejectEndorsement ຜູ້ ຮັບຮອງ ຕີ ກັບ → draft (with reason)
 *   cancel           any non-final → cancelled
 *   dispose          approved → disposed (confirm-then-update source registers)
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
            $record = new DisposalRecord([
                'request_number' => $this->nextNumber((int) now()->year),
                'title' => $data['title'] ?? null,
                'department_id' => $data['department_id'] ?? $actor->department_id ?? null,
                'note' => $data['note'] ?? null,
                'original_deposit_date' => $data['original_deposit_date'] ?? null,
                'original_receiver' => $data['original_receiver'] ?? null,
                'prepared_by_name' => $actor->display_name ?? $actor->email,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
            // guarded ຄໍລັມ — ຕັ້ງ server-side ເທົ່ານັ້ນ.
            $record->forceFill(['status' => 'draft', 'prepared_by_user_id' => $actor->id])->save();

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

            $this->lockSourceDeposits($record);   // ເຄື່ອງຝາກ ທີ່ ດຶງ ເຂົ້າ → ລັອກ ເປັນ 'disposal'
            $this->recordHistory($record, 'create', $actor);

            return $record;
        });
    }

    /** deposit records ທີ່ ຖືກ ອ້າງ ອີງ ໂດຍ item ຂອງ ໃບ ນີ້ (source_type='deposit'). */
    public function linkedDepositRecords(DisposalRecord $r): Collection
    {
        $depItemIds = $r->items()->where('source_type', 'deposit')->whereNotNull('source_id')->pluck('source_id');
        if ($depItemIds->isEmpty()) {
            return collect();
        }
        $recIds = DepositItem::whereIn('id', $depItemIds)->pluck('record_id')->unique()->filter();

        return DepositRecord::whereIn('id', $recIds)->get();
    }

    /** ພໍ ດຶງ ເຄື່ອງຝາກ ເຂົ້າ ໃບ ຈຳໜ່າຍ → ລັອກ deposit (status='disposal', ແກ້ ບໍ່ ໄດ້). */
    public function lockSourceDeposits(DisposalRecord $r): void
    {
        foreach ($this->linkedDepositRecords($r) as $dep) {
            if (! in_array($dep->status, ['disposal', 'disposed', 'cancelled'], true)) {
                $dep->update(['status' => 'disposal']);
            }
        }
    }

    /** ໃບ ຈຳໜ່າຍ ຖືກ ຍົກເລີກ/ລຶບ → ປົດ ລັອກ deposit (status='disposal' → 'stored'). */
    public function unlockSourceDeposits(DisposalRecord $r): void
    {
        foreach ($this->linkedDepositRecords($r) as $dep) {
            if ($dep->status === 'disposal') {
                $dep->update(['status' => 'stored']);
            }
        }
    }

    /** ປະຕິບັດ transition. */
    public function transition(DisposalRecord $r, string $action, $actor, array $opts = []): DisposalRecord
    {
        return DB::transaction(function () use ($r, $action, $actor, $opts) {
            match ($action) {
                'submit' => $this->doSubmit($r, $actor),
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
        $this->assert($r->signoffs()->whereNotNull('user_id')->exists(), 'ຕ້ອງ ມອບໝາຍ ຜູ້ ຮັບຮອງ ຢ່າງໜ້ອຍ 1 ຄົນ ກ່ອນ ສົ່ງ.');

        // ຮອບ ຮັບຮອງ ໃໝ່: ຮັກສາ ການ ມອບໝາຍ ໄວ້ ແຕ່ ຣີເຊັດ ລາຍເຊັນ ໃຫ້ ເຊັນ ຄືນ ໃສ່ ລິສ ທີ່ ອາດ ຖືກ ແກ້.
        $r->signoffs()->update([
            'signed_at' => null, 'comment' => null, 'recommendation' => null,
            'decision' => 'approved', 'notified_at' => null,
        ]);

        $r->status = 'in_review';
        $r->prepared_at = now();
        $r->reject_reason = null;
    }

    /**
     * ມອບໝາຍ ຜູ້ ຮັບຮອງ ຕໍ່ ບົດບາດ. $assignees: [role_key => ['user_id'=>?int, 'title'=>?string]].
     * Upsert ແຖວ signoff (pending); ປ່ຽນ ຄົນ → ຣີເຊັດ ລາຍເຊັນ ແຖວ ນັ້ນ; ວ່າງ → ລຶບ ແຖວ.
     *
     * @return array<int, array{signoff: DisposalSignoff, user: User}> ແຖວ ໃໝ່ ທີ່ ຕ້ອງ ແຈ້ງ
     */
    public function assignEndorsers(DisposalRecord $r, array $assignees, $actor): array
    {
        return DB::transaction(function () use ($r, $assignees, $actor) {
            $toNotify = [];
            foreach (DisposalRecord::STAGES as $roleKey => $cfg) {
                $sel = $assignees[$roleKey] ?? [];
                $userId = ! empty($sel['user_id']) ? (int) $sel['user_id'] : null;
                $title = ($sel['title'] ?? '') !== '' ? $sel['title'] : null;
                $row = $r->signoffs()->where('role_key', $roleKey)->first();

                if (! $userId) {
                    $row?->delete();

                    continue;
                }

                $user = User::find($userId);
                if (! $user) {
                    continue;
                }
                $name = $user->display_name ?: $user->email;

                if (! $row) {
                    $row = $r->signoffs()->create([
                        'role_key' => $roleKey, 'stage_order' => $cfg['order'],
                        'user_id' => $user->id, 'name' => $name, 'title' => $title,
                        'decision' => 'approved', 'signed_at' => null, 'assigned_by' => $actor->id,
                    ]);
                    $toNotify[] = ['signoff' => $row, 'user' => $user];

                    continue;
                }

                $changedUser = (int) $row->user_id !== $user->id;
                $row->fill(['user_id' => $user->id, 'name' => $name, 'title' => $title, 'stage_order' => $cfg['order'], 'assigned_by' => $actor->id]);
                if ($changedUser) {
                    $row->forceFill(['signed_at' => null, 'comment' => null, 'recommendation' => null, 'decision' => 'approved', 'notified_at' => null]);
                }
                $row->save();
                if ($row->isPending() && $row->notified_at === null) {
                    $toNotify[] = ['signoff' => $row, 'user' => $user];
                }
            }

            return $toNotify;
        });
    }

    /** ສົ່ງ ອີເມລ ລິ້ງ ຫາ ຜູ້ ຮັບຮອງ ທີ່ ຍັງ ຄ້າງ (pending + ຍັງ ບໍ່ ແຈ້ງ). @return int ຈຳນວນ ທີ່ ສົ່ງ */
    public function notifyPendingEndorsers(DisposalRecord $r): int
    {
        if ($r->status !== 'in_review') {
            return 0;
        }
        $sent = 0;
        foreach ($r->signoffs()->whereNotNull('user_id')->whereNull('signed_at')->whereNull('notified_at')->get() as $row) {
            $user = User::find($row->user_id);
            if (! $user || ! $user->email) {
                continue;
            }
            try {
                $user->notify(new DisposalEndorsementRequest($r, $row->role_key));
                $row->update(['notified_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                // ອີເມລ ລົ້ມ (SMTP ບໍ່ ຕັ້ງ) — ບໍ່ ຂັດ flow; ລິ້ງ ໃນ ແອັບ ຍັງ ໃຊ້ ໄດ້.
            }
        }

        return $sent;
    }

    /** ຜູ້ ຖືກ ມອບໝາຍ ເຊັນ ຮັບຮອງ ຊ່ອງ ຕົນ (ອິດສະລະ ລຳດັບ). */
    public function endorse(DisposalRecord $r, string $roleKey, $actor, array $opts = []): DisposalRecord
    {
        return DB::transaction(function () use ($r, $roleKey, $actor, $opts) {
            $row = $r->signoffs()->where('role_key', $roleKey)->first();
            $this->assert($row !== null && $row->user_id !== null, 'ຍັງ ບໍ່ ໄດ້ ມອບໝາຍ ຊ່ອງ ນີ້.');
            $this->assert($r->status === 'in_review', 'ຮັບຮອງ ໄດ້ ສະເພາະ ຕອນ ໃບ ຢູ່ ຮອບ ຮັບຮອງ.');
            $this->assert((int) $row->user_id === (int) $actor->id || $actor->is_super_admin, 'ບໍ່ ແມ່ນ ຊ່ອງ ຮັບຮອງ ຂອງ ທ່ານ.');

            $row->update([
                'decision' => 'approved', 'signed_at' => now(),
                'comment' => $opts['comment'] ?? null,
                'recommendation' => $opts['recommendation'] ?? null,
            ]);

            $r->load('signoffs');
            if ($r->endorsementComplete()) {
                // C-Level ເຊັນ ຄົບ → ຈຳໜ່າຍ ສຳເລັດ ທັນທີ + ອັບເດດ ທະບຽນ ຕົ້ນທາງ (ປິດ deposit ເປັນ 'disposed')
                $r->status = 'disposed';
                $this->updateSourceRegisters($r);
                $r->registers_updated_at = now();
                $r->registers_updated_by = $actor->id;
            }
            $r->updated_by = $actor->id;
            $r->save();
            $this->recordHistory($r, 'endorse', $actor, $opts['comment'] ?? null);

            return $r->refresh();
        });
    }

    /** ຜູ້ ຖືກ ມອບໝາຍ ຕີ ກັບ ຊ່ອງ ຕົນ → ໃບ ກັບ draft ພ້ອມ ເຫດຜົນ. */
    public function rejectEndorsement(DisposalRecord $r, string $roleKey, $actor, array $opts = []): DisposalRecord
    {
        return DB::transaction(function () use ($r, $roleKey, $actor, $opts) {
            $row = $r->signoffs()->where('role_key', $roleKey)->first();
            $this->assert($row !== null && $row->user_id !== null, 'ຍັງ ບໍ່ ໄດ້ ມອບໝາຍ ຊ່ອງ ນີ້.');
            $this->assert($r->status === 'in_review', 'ຕີ ກັບ ໄດ້ ສະເພາະ ຕອນ ໃບ ຢູ່ ຮອບ ຮັບຮອງ.');
            $this->assert((int) $row->user_id === (int) $actor->id || $actor->is_super_admin, 'ບໍ່ ແມ່ນ ຊ່ອງ ຮັບຮອງ ຂອງ ທ່ານ.');
            $this->assert(! empty($opts['reason']), 'ຕ້ອງ ໃສ່ ເຫດຜົນ ຕີ ກັບ.');

            $row->update(['decision' => 'rejected', 'signed_at' => now(), 'comment' => $opts['reason']]);
            $r->status = 'draft';
            $r->reject_reason = $opts['reason'];
            $r->updated_by = $actor->id;
            $r->save();
            $this->recordHistory($r, 'endorse-reject', $actor, $opts['reason']);

            return $r->refresh();
        });
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
                // ປິດ deposit ເປັນ ລິສ ຕາຍ — ເຄື່ອງ ບໍ່ ມີ ຕົວຕົນ ແລ້ວ
                DepositRecord::whereKey($di->record_id)->update(['status' => 'disposed']);
            }
        }
    }

    private function doCancel(DisposalRecord $r, array $opts): void
    {
        $this->assert(! in_array($r->status, ['disposed', 'cancelled'], true), 'ຍົກເລີກ ບໍ່ ໄດ້ ໃນ ສະຖານະ ນີ້.');
        $this->unlockSourceDeposits($r);   // ປົດ ລັອກ ເຄື່ອງຝາກ ທີ່ ດຶງ ໄວ້
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
