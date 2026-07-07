<?php

use App\Livewire\Equipment\MaintenanceTemplates;
use App\Models\Equipment;
use App\Models\MaintenanceTemplate;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('admin can create a maintenance template tied to an equipment (category derived, blanks dropped)', function () {
    actingAs(User::factory()->create(['is_super_admin' => true]));
    $e = Equipment::create(['asset_code' => 'FL-9', 'name' => 'Forklift 9', 'category' => 'Forklift', 'quantity' => 1]);

    Livewire::test(MaintenanceTemplates::class)
        ->call('newTemplate')
        ->set('tName', 'Forklift PM')
        ->set('tEquipmentId', $e->id)
        ->set('tItems', [
            ['label' => 'ປ່ຽນ ນ້ຳມັນ ເຄື່ອງ', 'freqs' => ['quarterly', 'annual']],
            ['label' => 'ກວດ ລະດັບ ນ້ຳມັນ', 'freqs' => ['daily']],
            ['label' => '', 'freqs' => ['monthly']],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $t = MaintenanceTemplate::first();
    expect($t->name)->toBe('Forklift PM');
    expect($t->equipment_id)->toBe($e->id);
    expect($t->category)->toBe('Forklift');   // ດຶງ ຈາກ ເຄື່ອງ ໂດຍ ອັດຕະໂນມັດ
    // ຂໍ້ ວ່າງ ຖືກ ຕັດ · freqs ຄັດ ໃຫ້ ຢູ່ ໃນ ຮອບ ທີ່ ຮັບຮອງ (ວັນ/ເດືອນ/ໄຕມາດ/ປີ)
    expect($t->normalizedItems())->toBe([
        ['label' => 'ປ່ຽນ ນ້ຳມັນ ເຄື່ອງ', 'freqs' => ['quarterly', 'annual']],
        ['label' => 'ກວດ ລະດັບ ນ້ຳມັນ', 'freqs' => ['daily']],
    ]);
});

test('creating a template requires selecting an equipment', function () {
    actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(MaintenanceTemplates::class)
        ->call('newTemplate')
        ->set('tName', 'No equipment')
        ->call('save')
        ->assertHasErrors('tEquipmentId');

    expect(MaintenanceTemplate::count())->toBe(0);
});

test('editing a template updates it without creating a new one', function () {
    actingAs(User::factory()->create(['is_super_admin' => true]));
    $e = Equipment::create(['asset_code' => 'PMP-1', 'name' => 'Pump', 'category' => 'Pump', 'quantity' => 1]);
    $t = MaintenanceTemplate::create([
        'name' => 'Old', 'equipment_id' => $e->id, 'category' => 'Pump',
        'items' => [['label' => 'A', 'freqs' => []]], 'is_active' => true,
    ]);

    Livewire::test(MaintenanceTemplates::class)
        ->call('editTemplate', $t->id)
        ->assertSet('tName', 'Old')
        ->assertSet('tEquipmentId', $e->id)
        ->set('tName', 'New name')
        ->set('tActive', false)
        ->call('save')
        ->assertHasNoErrors();

    expect(MaintenanceTemplate::count())->toBe(1);
    expect($t->fresh()->name)->toBe('New name');
    expect($t->fresh()->is_active)->toBeFalse();
});

test('an admin can delete a maintenance template', function () {
    actingAs(User::factory()->create(['is_super_admin' => true]));
    $t = MaintenanceTemplate::create(['name' => 'Doomed', 'items' => [], 'is_active' => true]);

    Livewire::test(MaintenanceTemplates::class)
        ->call('delete', $t->id);

    expect(MaintenanceTemplate::find($t->id))->toBeNull();
});

test('a requester cannot access the maintenance template manager', function () {
    $u = User::factory()->create();
    $u->syncRoles(['requester']);
    actingAs($u);

    Livewire::test(MaintenanceTemplates::class)->assertForbidden();
});

test('a department-scoped admin cannot access the central template manager', function () {
    $u = User::factory()->create();
    $u->syncRoles(['department_admin']);
    actingAs($u);

    Livewire::test(MaintenanceTemplates::class)->assertForbidden();
});
