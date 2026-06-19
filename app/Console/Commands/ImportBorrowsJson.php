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
    protected $signature = 'borrow:import-json {path} {--by= : fallback user id ສຳລັບ borrower ທີ່ບໍ່ພົບ}';

    protected $description = 'Import borrow records from a WIS export JSON';

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

        $imported = 0;
        $skipped = 0;
        $unmatched = 0;

        foreach ($rows as $rec) {
            $num = $rec['request_number'] ?? null;
            if (! $num || BorrowRecord::where('request_number', $num)->exists()) {
                $skipped++;

                continue;
            }

            $email = mb_strtolower($rec['borrower_email'] ?? '');
            $uid = $byEmail[$email] ?? $fallback;
            if (! isset($byEmail[$email])) {
                $unmatched++;
            }

            $borrowDate = $rec['borrow_date'] ?: ($rec['created_at'] ? Carbon::parse($rec['created_at'])->toDateString() : Carbon::today()->toDateString());
            $period = (int) ($rec['period_days'] ?? 7) ?: 7;
            $planned = $rec['planned_return_date'] ?: Carbon::parse($borrowDate)->addDays($period)->toDateString();

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
                    'actual_return_date' => $rec['actual_return_date'] ?: null,
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
                    'status' => $rec['status'] ?? 'draft',
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
        $this->info("✓ Imported: {$imported} · Skipped: {$skipped} · borrower unmatched→fallback: {$unmatched}");

        return self::SUCCESS;
    }

    /** ISO/date string → MySQL datetime (null-safe). */
    private function dt($v): ?string
    {
        return $v ? Carbon::parse($v)->format('Y-m-d H:i:s') : null;
    }
}
