<?php

use App\Livewire\Borrow\Create;
use App\Models\Equipment;
use App\Models\User;
use App\Services\BorrowService;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create(['is_super_admin' => true, 'display_name' => 'Admin']);
});

test('choosing option 2 pulls an item from the Equipment register (stores equipment_id)', function () {
    $eq = Equipment::create(['asset_code' => 'EQ-0001', 'name' => 'Welding machine']);

    actingAs($this->admin);
    Livewire::test(Create::class)
        ->set('borrow_type', 'tools_equipment')
        ->call('addEquipmentItem', $eq->id)
        ->assertSet('items.0.equipment_id', $eq->id)
        ->assertSet('items.0.item_name', 'Welding machine');
});

test('BorrowService stores equipment_id on the borrow item', function () {
    $eq = Equipment::create(['asset_code' => 'EQ-0002', 'name' => 'Forklift']);

    $record = app(BorrowService::class)->createDraft([
        'borrow_type' => 'tools_equipment',
        'purpose' => 'move pallets',
        'borrow_date' => now()->toDateString(),
        'period_days' => 5,
        'items' => [['equipment_id' => $eq->id, 'item_name' => $eq->name, 'qty' => 1]],
    ], $this->admin);

    $item = $record->items->first();
    expect($item->equipment_id)->toBe($eq->id);
    expect($item->item_name)->toBe('Forklift');
    expect($item->equipment->asset_code)->toBe('EQ-0002');   // relation works
});
