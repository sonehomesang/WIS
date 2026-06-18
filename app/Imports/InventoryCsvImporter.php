<?php

namespace App\Imports;

use App\Models\InventoryItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use League\Csv\Reader;

/**
 * ນຳເຂົ້າ inventory ຈາກ CSV (17K+ rows). ໃຊ້ league/csv stream + bulk insert.
 *
 * Header ທີ່ຮອງຮັບ (case-insensitive): name*, description, category, brand, model,
 * serial_number, quantity, min_quantity, unit, shelf_label, status, slug.
 * location/building/room = NULL ສະເໝີ (id mapping ຕ່າງ — ເບິ່ງ PROGRESS).
 */
class InventoryCsvImporter
{
    private const STATUSES = ['available', 'borrowed', 'maintenance', 'low-stock'];

    private const CHUNK = 500;

    /** @var array<string,bool> slug ທີ່ມີໃນ DB ຫຼື ເຫັນແລ້ວໃນ file (dedup). */
    private array $seenSlugs = [];

    /**
     * @return array{imported:int, skipped:int, errors:array<int,string>}
     */
    public function import(string $path, ?int $userId = null): array
    {
        $this->seenSlugs = DB::table('inventory_items')->pluck('slug')->flip()->map(fn () => true)->all();

        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $buffer = [];
        $line = 1; // header = line 1

        foreach ($csv->getRecords() as $record) {
            $line++;
            $row = $this->normalizeKeys($record);

            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                $skipped++;
                $errors[] = "ແຖວ {$line}: ບໍ່ມີ name → ຂ້າມ";

                continue;
            }

            $slug = $this->resolveSlug($row['slug'] ?? null, $name);
            if ($slug === null) {
                $skipped++; // slug ຊ້ຳ (ມີໃນ DB ຫຼື file ແລ້ວ)

                continue;
            }

            $status = strtolower(trim((string) ($row['status'] ?? '')));
            if (! in_array($status, self::STATUSES, true)) {
                $status = 'available';
            }

            $now = Carbon::now();
            $buffer[] = [
                'slug' => $slug,
                'name' => Str::limit($name, 256, ''),
                'description' => $this->nullable($row['description'] ?? null),
                'category' => $this->nullable($row['category'] ?? null, 128),
                'brand' => $this->nullable($row['brand'] ?? null, 128),
                'model' => $this->nullable($row['model'] ?? null, 128),
                'serial_number' => $this->nullable($row['serial_number'] ?? null, 128),
                'quantity' => $this->toInt($row['quantity'] ?? null),
                'min_quantity' => $this->toInt($row['min_quantity'] ?? null),
                'unit' => $this->nullable($row['unit'] ?? null, 32),
                'shelf_label' => $this->nullable($row['shelf_label'] ?? null, 64),
                'status' => $status,
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $imported++;

            if (count($buffer) >= self::CHUNK) {
                InventoryItem::insert($buffer);
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            InventoryItem::insert($buffer);
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    /** Lowercase + trim header keys ເພື່ອ map ບໍ່ສົນ case/ຊ່ອງ. */
    private function normalizeKeys(array $record): array
    {
        $out = [];
        foreach ($record as $k => $v) {
            $out[strtolower(trim((string) $k))] = $v;
        }

        return $out;
    }

    /** ສ້າງ slug ທີ່ unique; return null ຖ້າຊ້ຳ (skip). */
    private function resolveSlug(?string $provided, string $name): ?string
    {
        $provided = trim((string) $provided);
        if ($provided !== '') {
            $slug = Str::slug($provided) ?: Str::lower(preg_replace('/[^A-Za-z0-9\-]/', '', $provided));
            if ($slug === '' || isset($this->seenSlugs[$slug])) {
                return null;
            }
            $this->seenSlugs[$slug] = true;

            return $slug;
        }

        // ບໍ່ມີ slug ໃຫ້ → gen ຈາກ name + ຕໍ່ທ້າຍໃຫ້ unique
        $base = Str::slug($name) ?: 'item';
        $slug = $base;
        $i = 2;
        while (isset($this->seenSlugs[$slug])) {
            $slug = $base.'-'.$i++;
        }
        $this->seenSlugs[$slug] = true;

        return $slug;
    }

    private function nullable(?string $v, ?int $max = null): ?string
    {
        $v = trim((string) $v);
        if ($v === '') {
            return null;
        }

        return $max ? Str::limit($v, $max, '') : $v;
    }

    private function toInt(?string $v): int
    {
        return max(0, (int) trim((string) $v));
    }
}
