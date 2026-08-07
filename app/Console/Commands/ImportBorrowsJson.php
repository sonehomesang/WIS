<?php

namespace App\Console\Commands;

use App\Models\BorrowRecord;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * borrow:import-json — import borrow records ຈາກ WIS export JSON → WH (Phase 6.6.6).
 *
 * Map: borrower email → WH user (fallback --by) · unit/dept/item_id = NULL · photos skip (6.11).
 * Idempotent: skip ຖ້າ request_number ມີແລ້ວ.
 */
class ImportBorrowsJson extends Command
{
    protected $signature = 'borrow:import-json {path} {--by= : fallback user id ສຳລັບ borrower ທີ່ບໍ່ພົບ} {--refresh : ອັບເດດ ໃບ ທີ່ ມີ ແລ້ວ (status/return/items) ແທນ ການ ຂ້າມ}';

    protected $description = 'Import borrow records from a WIS export JSON';

    /** ສະຖານະ ທີ່ ຮັບ ໄດ້ (whitelist ກັນ ຄ່າ ນອກ state-machine ຈາກ JSON). */
    private const STATUSES = ['draft', 'acknowledged', 'approved', 'active', 'overdue', 'returned', 'cancelled'];

    public function handle(): int
    {
        $path = $this->argument('path');
        if (! is_file($path)) {
            $this->error("ບໍ່ພົບໄຟລ໌: {$path}");

            return self::FAILURE;
        }

        $rows = json_decode(file_get_contents($path), true);
        if (! is_array($rows)) {
            $this->error('JSON ບໍ່ຖືກຕ້ອງ');

            return self::FAILURE;
        }

        $fallback = $this->option('by')
            ? (int) $this->option('by')
            : (User::where('is_super_admin', true)->value('id') ?? User::value('id'));
        $byEmail = User::query()->get(['id', 'email'])
            ->mapWithKeys(fn ($u) => [mb_strtolower($u->email) => $u->id])->all();

        $refresh = (bool) $this->option('refresh');
        $imported = 0;
        $skipped = 0;
        $refreshed = 0;
        $unmatched = 0;

        foreach ($rows as $rec) {
            $num = $rec['request_number'] ?? null;
            if (! $num) {
                $skipped++;

                continue;
            }
            $existing = BorrowRecord::where('request_number', $num)->first();
            if ($existing) {
                // ໃບ ມີ ແລ້ວ: refresh mode → ອັບເດດ status/return/items · ບໍ່ ດັ່ງນັ້ນ → ຂ້າມ (idempotent).
                if ($refresh) {
                    $this->refreshRecord($existing, $rec);
                    $refreshed++;
                } else {
                    $skipped++;
                }

                continue;
            }

            $email = mb_strtolower($rec['borrower_email'] ?? '');
            $uid = $byEmail[$email] ?? $fallback;
            if (! isset($byEmail[$email])) {
                $unmatched++;
            }

            $borrowDate = ($rec['borrow_date'] ?? null) ?: (($rec['created_at'] ?? null) ? Carbon::parse($rec['created_at'])->toDateString() : Carbon::today()->toDateString());
            $period = (int) ($rec['period_days'] ?? 7) ?: 7;
            $planned = ($rec['planned_return_date'] ?? null) ?: Carbon::parse($borrowDate)->addDays($period)->toDateString();

            DB::transaction(function () use ($rec, $num, $uid, $email, $borrowDate, $period, $planned) {
                $r = BorrowRecord::create([
                    'request_number' => $num,
                    'borrower_user_id' => $uid,
                    'borrower_email' => $email ?: '—',
                    'borrower_name' => $rec['borrower_name'] ?? $email,
                    'borrow_type' => in_array($rec['borrow_type'] ?? '', ['new_inventory', 'tools_equipment', 'deposited_tools', 'others'], true) ? $rec['borrow_type'] : 'new_inventory',
                    'purpose' => $rec['purpose'] ?? null,
                    'remark' => $rec['remark'] ?? null,
                    'borrow_date' => $borrowDate,
                    'period_days' => $period,
                    'planned_return_date' => $planned,
                    'actual_return_date' => ($rec['actual_return_date'] ?? null) ?: null,
                    'requires_acknowledge' => (bool) ($rec['requires_acknowledge'] ?? false),
                    'acknowledge_email' => $rec['acknowledge_email'] ?? null,
                    'acknowledge_name' => $rec['acknowledge_name'] ?? null,
                    'acknowledged_at' => $rec['acknowledged_at'] ?? null,
                    'approver_email' => $rec['approver_email'] ?? null,
                    'approver_name' => $rec['approver_name'] ?? null,
                    'approved_at' => $rec['approved_at'] ?? null,
                    'warehouse_staff_name' => $rec['warehouse_staff_name'] ?? null,
                    'taken_at' => $rec['taken_at'] ?? null,
                    'returned_at' => $rec['returned_at'] ?? null,
                    'status' => in_array($rec['status'] ?? '', self::STATUSES, true) ? $rec['status'] : 'draft',
                    'cancel_reason' => $rec['cancel_reason'] ?? null,
                    'created_by' => $uid,
                    'updated_by' => $uid,
                ]);

                foreach (array_values($rec['items'] ?? []) as $i => $it) {
                    $r->items()->create([
                        'item_id' => null,
                        'item_name' => $it['item_name'] ?? ('item '.($i + 1)),
                        'qty' => max(1, (int) ($it['qty'] ?? 1)),
                        'return_qty' => isset($it['return_qty']) ? (int) $it['return_qty'] : null,
                        'condition_on_take' => $it['condition_on_take'] ?? null,
                        'condition_on_return' => $it['condition_on_return'] ?? null,
                        'sort_order' => $i,
                    ]);
                }

                foreach (array_values($rec['history'] ?? []) as $h) {
                    $r->history()->create([
                        'action' => $h['action'] ?? 'update',
                        'status' => $h['status'] ?? '',
                        'user_name' => $h['user_name'] ?? null,
                        'role' => $h['role'] ?? null,
                        'comment' => $h['comment'] ?? null,
                        'created_at' => $this->dt($h['created_at'] ?? null) ?? now(),
                    ]);
                }

                // preserve source timestamps
                DB::table('borrow_records')->where('id', $r->id)->update([
                    'created_at' => $this->dt($rec['created_at'] ?? null) ?? now(),
                    'updated_at' => $this->dt($rec['updated_at'] ?? null) ?? now(),
                ]);
            });

            $imported++;
        }

        $this->newLine();
        $this->info("✓ Imported: {$imported} · Refreshed: {$refreshed} · Skipped: {$skipped} · borrower unmatched→fallback: {$unmatched}");

        return self::SUCCESS;
    }

    /**
     * refresh mode — ອັບເດດ ໃບ ທີ່ ມີ ແລ້ວ ໃນ ບ່ອນ (ຮັກສາ id/ຮູບ/history ເດີມ):
     * ສະຖານະ + ວັນ ຄືນ + ຕິກ ຮັບ + ຈຳນວນ/ສະພາບ ຄືນ ຕໍ່ ລາຍການ (ຈັບ ຄູ່ ຕາມ sort_order).
     */
    private function refreshRecord(BorrowRecord $r, array $rec): void
    {
        DB::transaction(function () use ($r, $rec) {
            $r->fill([
                'status' => in_array($rec['status'] ?? '', self::STATUSES, true) ? $rec['status'] : $r->status,
                'actual_return_date' => ($rec['actual_return_date'] ?? null) ?: $r->actual_return_date,
                'returned_at' => $rec['returned_at'] ?? $r->returned_at,
                'taken_at' => $rec['taken_at'] ?? $r->taken_at,
                'acknowledged_at' => $rec['acknowledged_at'] ?? $r->acknowledged_at,
                'approved_at' => $rec['approved_at'] ?? $r->approved_at,
                'warehouse_staff_name' => $rec['warehouse_staff_name'] ?? $r->warehouse_staff_name,
                'updated_by' => auth()->id() ?? $r->updated_by,
            ])->save();

            $items = $r->items()->orderBy('sort_order')->orderBy('id')->get();
            foreach (array_values($rec['items'] ?? []) as $i => $it) {
                $item = $items[$i] ?? null;
                if ($item) {
                    $item->update([
                        'return_qty' => isset($it['return_qty']) ? (int) $it['return_qty'] : $item->return_qty,
                        'condition_on_take' => $it['condition_on_take'] ?? $item->condition_on_take,
                        'condition_on_return' => $it['condition_on_return'] ?? $item->condition_on_return,
                    ]);
                }
            }

            DB::table('borrow_records')->where('id', $r->id)->update([
                'updated_at' => $this->dt($rec['updated_at'] ?? null) ?? now(),
            ]);
        });
    }

    /** ISO/date string → MySQL datetime (null-safe). */
    private function dt($v): ?string
    {
        return $v ? Carbon::parse($v)->format('Y-m-d H:i:s') : null;
    }
}
