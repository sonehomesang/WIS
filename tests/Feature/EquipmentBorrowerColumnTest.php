<?php

use App\Livewire\Equipment\Index;
use App\Models\BorrowItem;
use App\Models\BorrowRecord;
use App\Models\Equipment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->staff = User::factory()->create();
    $this->staff->syncRoles(['warehouse_staff']);
    $this->borrower = User::factory()->create();
});

function makeEquipmentBorrow(int $equipmentId, int $borrowerId, string $status, string $name, string $reqNo): void
{
    $rec = BorrowRecord::create([
        'request_number' => $reqNo,
        'borrower_user_id' => $borrowerId,
        'borrower_email' => 'b@wh.la',
        'borrower_name' => $name,
        'borrow_date' => now(),
        'planned_return_date' => now()->addDays(7),
        'status' => $status,
    ]);
    BorrowItem::create(['record_id' => $rec->id, 'equipment_id' => $equipmentId, 'item_name' => 'Drill', 'qty' => 1]);
}

test('the register shows the current borrower from an active borrow', function () {
    $eq = Equipment::create(['asset_code' => 'EQ-B1', 'name' => 'Drill', 'quantity' => 1]);
    makeEquipmentBorrow($eq->id, $this->borrower->id, 'active', 'ທ. ບຸນມີ', 'BR2026-0001');

    expect($eq->fresh()->currentBorrowers())->toBe(['ທ. ບຸນມີ']);

    actingAs($this->staff);
    Livewire::test(Index::class)->assertSee('ທ. ບຸນມີ');
});

test('a returned borrow does not count as current borrower', function () {
    $eq = Equipment::create(['asset_code' => 'EQ-B2', 'name' => 'Saw', 'quantity' => 1]);
    makeEquipmentBorrow($eq->id, $this->borrower->id, 'returned', 'ທ. ສົມ', 'BR2026-0002');

    expect($eq->fresh()->currentBorrowers())->toBe([]);
});
