<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Super-admin utility to wipe UAT / mock-up data before Go-Live so it doesn't
 * mingle with real records. Truncates the selected groups (resets ID counters)
 * and keeps users, roles, settings, org and the chosen master catalogues.
 */
#[Layout('layouts.app')]
class ClearTestData extends Component
{
    /** @var array<int,string> selected group keys */
    public array $selected = [];

    public string $confirm = '';

    /** group => [label, master?, count(main table), tables(child→parent order)] */
    public const GROUPS = [
        // ── transactions (safe to clear for a clean go-live) ──
        'borrow' => ['label' => 'ຢືມ ເຄື່ອງ · Borrow', 'master' => false, 'count' => 'borrow_records',
            'tables' => ['borrow_return_event_lines', 'borrow_return_events', 'borrow_item_photos', 'borrow_items', 'borrow_history', 'borrow_records']],
        'deposit' => ['label' => 'ຝາກ ເຄື່ອງ · Deposit', 'master' => false, 'count' => 'deposit_records',
            'tables' => ['deposit_item_photos', 'deposit_items', 'deposit_history', 'deposit_records']],
        'disposal' => ['label' => 'ຈຳໜ່າຍ · Disposal', 'master' => false, 'count' => 'disposal_records',
            'tables' => ['disposal_signoffs', 'disposal_items', 'disposal_history', 'disposal_records']],
        'request' => ['label' => 'ຂໍ ເບີກ · Request', 'master' => false, 'count' => 'material_requests',
            'tables' => ['material_request_item_photos', 'material_request_items', 'material_request_history', 'material_requests']],
        'da' => ['label' => 'DA Claims', 'master' => false, 'count' => 'discrepancy_advices',
            'tables' => ['discrepancy_advice_photos', 'discrepancy_advice_items', 'discrepancy_advice_history', 'discrepancy_advices']],
        'oga' => ['label' => 'OGA', 'master' => false, 'count' => 'outwards_goods_advices',
            'tables' => ['oga_photos', 'oga_items', 'oga_history', 'outwards_goods_advices']],
        'expo' => ['label' => 'Expo Info', 'master' => false, 'count' => 'expo_events',
            'tables' => ['expo_company_files', 'expo_contacts', 'expo_attendees', 'expo_companies', 'expo_events']],
        'equipment_activity' => ['label' => 'ກວດ/ບຳລຸງ ອຸປະກອນ · Inspections & maintenance', 'master' => false, 'count' => 'equipment_maintenances',
            'tables' => ['equipment_inspections', 'equipment_maintenances']],
        'area_inspection' => ['label' => 'ກວດ ສະຖານທີ່ · Area inspections', 'master' => false, 'count' => 'area_inspections',
            'tables' => ['area_inspections']],
        'notifications' => ['label' => 'ການ ແຈ້ງເຕືອນ · Notifications', 'master' => false, 'count' => 'notifications',
            'tables' => ['notifications']],
        // ── master catalogues (DANGER — only if these are test data too) ──
        'inventory' => ['label' => 'ສາງ · Inventory items', 'master' => true, 'count' => 'inventory_items',
            'tables' => ['inventory_item_photos', 'inventory_items']],
        'equipment_assets' => ['label' => 'ອຸປະກອນ · Equipment assets', 'master' => true, 'count' => 'equipment',
            'tables' => ['equipment_photos', 'equipment']],
        'materials' => ['label' => 'ວັດຖຸ · Materials / Catalog', 'master' => true, 'count' => 'materials',
            'tables' => ['material_images', 'material_price_history', 'materials']],
        'suppliers' => ['label' => 'ຜູ້ສະໜອງ · Suppliers', 'master' => true, 'count' => 'suppliers',
            'tables' => ['supplier_vat_changes', 'supplier_contracts', 'suppliers']],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->is_super_admin, 403);
    }

    public function clear(): void
    {
        abort_unless(auth()->user()->is_super_admin, 403);

        $this->validate([
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['in:'.implode(',', array_keys(self::GROUPS))],
            'confirm' => ['required', 'in:CLEAR'],
        ], [
            'selected.required' => 'ເລືອກ ຢ່າງໜ້ອຍ 1 ກຸ່ມ ທີ່ ຈະ ລຶບ.',
            'confirm.in' => 'ພິມ CLEAR (ໂຕ ໃຫຍ່) ເພື່ອ ຢືນຢັນ.',
        ]);

        // child → parent order across all selected groups; de-dup keeping first occurrence
        $tables = [];
        foreach ($this->selected as $g) {
            foreach (self::GROUPS[$g]['tables'] as $t) {
                $tables[$t] = true;
            }
        }
        $tables = array_keys($tables);

        $summary = [];
        Schema::disableForeignKeyConstraints();
        try {
            foreach ($tables as $t) {
                $n = DB::table($t)->count();
                DB::table($t)->truncate();   // resets auto-increment → records restart from 1
                if ($n > 0) {
                    $summary[$t] = $n;
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        Log::warning('[clear-test-data] user '.auth()->id().' cleared groups: '.implode(',', $this->selected).' · rows: '.json_encode($summary));

        $this->reset(['selected', 'confirm']);
        session()->flash('cleared', $summary);
        $this->dispatch('cleared');
    }

    public function render(): View
    {
        $counts = [];
        foreach (self::GROUPS as $key => $g) {
            $counts[$key] = Schema::hasTable($g['count']) ? DB::table($g['count'])->count() : 0;
        }

        return view('livewire.settings.clear-test-data', [
            'groups' => self::GROUPS,
            'counts' => $counts,
        ]);
    }
}
