<?php

use App\Livewire\Equipment\Categories;
use App\Livewire\Equipment\Index;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('super admin can create an equipment category', function () {
    actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(Categories::class)
        ->call('newCategory')
        ->set('cName', 'Crane')
        ->call('save')
        ->assertHasNoErrors();

    expect(EquipmentCategory::where('name', 'Crane')->exists())->toBeTrue();
});

test('duplicate category names are rejected', function () {
    EquipmentCategory::create(['name' => 'Crane', 'is_active' => true]);
    actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(Categories::class)
        ->call('newCategory')
        ->set('cName', 'Crane')
        ->call('save')
        ->assertHasErrors('cName');
});

test('a requester cannot manage equipment categories', function () {
    $u = User::factory()->create();
    $u->syncRoles(['requester']);
    actingAs($u);

    Livewire::test(Categories::class)->assertForbidden();
});

test('a department_admin cannot manage equipment categories', function () {
    $u = User::factory()->create();
    $u->syncRoles(['department_admin']);
    actingAs($u);

    Livewire::test(Categories::class)->assertForbidden();
});

test('linking a responsible user copies the user name onto the equipment', function () {
    $staff = User::factory()->create();
    $staff->syncRoles(['warehouse_staff']);
    $person = User::factory()->create(['display_name' => 'Somsak P']);
    actingAs($staff);

    Livewire::test(Index::class)
        ->call('newItem')
        ->set('asset_code', 'EQ-R1')
        ->set('name', 'Crane')
        ->set('quantity', 1)
        ->set('responsible_user_id', $person->id)
        ->call('save')
        ->assertHasNoErrors();

    $eq = Equipment::where('asset_code', 'EQ-R1')->first();
    expect($eq->responsible_user_id)->toBe($person->id);
    expect($eq->responsible_name)->toBe('Somsak P');       // ຄັດ ຊື່ ຜູ້ໃຊ້ ໃສ່ ໃຫ້
    expect($eq->responsibleLabel())->toBe('Somsak P');
});
