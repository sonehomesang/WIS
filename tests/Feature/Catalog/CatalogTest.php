<?php

use App\Livewire\Catalog\Index;
use App\Models\Material;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function aCatalogSupplier(string $name = 'Acme'): Supplier
{
    return Supplier::create(['slug' => Str::slug($name).'-'.uniqid(), 'name' => $name, 'is_active' => true]);
}

test('admin can create a material and initial price is logged', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $s = aCatalogSupplier();

    Livewire::test(Index::class)
        ->call('newItem')
        ->set('supplier_id', $s->id)
        ->set('category', 'Valve')
        ->set('description', 'Solenoid valve 24V')
        ->set('unit_price', '150.00')
        ->set('currency', 'THB')
        ->set('contract_number', 'CT-001')
        ->call('save')
        ->assertHasNoErrors();

    $m = Material::first();
    expect($m)->not->toBeNull();
    expect($m->currency)->toBe('THB');
    expect($m->last_price_update)->not->toBeNull();
    expect($m->priceHistory()->count())->toBe(1);
    expect((float) $m->priceHistory()->first()->unit_price)->toBe(150.00);
});

test('changing price appends a history row without touching old ones', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $s = aCatalogSupplier();
    $m = Material::create(['supplier_id' => $s->id, 'category' => 'X', 'description' => 'Item', 'unit_price' => 100, 'currency' => 'THB']);
    expect($m->priceHistory()->count())->toBe(1);

    Livewire::test(Index::class)
        ->call('editItem', $m->id)
        ->set('unit_price', '120.00')
        ->call('save')
        ->assertHasNoErrors();

    $m->refresh();
    expect((float) $m->unit_price)->toBe(120.00);
    expect($m->priceHistory()->count())->toBe(2); // old 100 kept + new 120
    expect((float) $m->priceHistory()->orderByDesc('id')->first()->unit_price)->toBe(120.00);
});

test('editing without price change does not add history', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $s = aCatalogSupplier();
    $m = Material::create(['supplier_id' => $s->id, 'category' => 'X', 'description' => 'Item', 'unit_price' => 100, 'currency' => 'THB']);

    Livewire::test(Index::class)
        ->call('editItem', $m->id)
        ->set('description', 'Item renamed')
        ->call('save')
        ->assertHasNoErrors();

    expect($m->refresh()->priceHistory()->count())->toBe(1);
});

test('toggle active flips the flag', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $s = aCatalogSupplier();
    $m = Material::create(['supplier_id' => $s->id, 'category' => 'X', 'description' => 'Item', 'is_active' => true]);

    Livewire::test(Index::class)->call('toggle', $m->id);
    expect($m->refresh()->is_active)->toBeFalse();
});

test('default currency is THB', function () {
    $s = aCatalogSupplier();
    $m = Material::create(['supplier_id' => $s->id, 'category' => 'X', 'description' => 'Item']);
    expect($m->refresh()->currency)->toBe('THB');
});

test('supplier role sees only own materials', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $s1 = aCatalogSupplier('S-One');
    $s2 = aCatalogSupplier('S-Two');
    Material::create(['supplier_id' => $s1->id, 'category' => 'X', 'description' => 'MINE-ITEM']);
    Material::create(['supplier_id' => $s2->id, 'category' => 'X', 'description' => 'OTHER-ITEM']);

    $sup = User::factory()->create(['is_super_admin' => false, 'supplier_id' => $s1->id]);
    $sup->assignRole('supplier');
    $this->actingAs($sup);

    Livewire::test(Index::class)
        ->assertSee('MINE-ITEM')
        ->assertDontSee('OTHER-ITEM');
});

test('supplier-scoped user cannot reassign material to another supplier', function () {
    $own = aCatalogSupplier('Own');
    $other = aCatalogSupplier('Other');
    $sup = User::factory()->create(['is_super_admin' => false, 'supplier_id' => $own->id]);
    $sup->assignRole('supplier');
    $this->actingAs($sup);

    Livewire::test(Index::class)
        ->call('newItem')
        ->set('supplier_id', $other->id)   // try to assign to someone else
        ->set('category', 'X')
        ->set('description', 'Sneaky item')
        ->set('currency', 'THB')
        ->call('save')
        ->assertHasNoErrors();

    $m = Material::where('description', 'Sneaky item')->first();
    expect($m->supplier_id)->toBe($own->id);   // forced back to own supplier
});

test('viewer without create permission cannot open create', function () {
    $viewer = User::factory()->create(['is_super_admin' => false]);
    $viewer->givePermissionTo('catalog.view');
    $this->actingAs($viewer);

    Livewire::test(Index::class)->call('newItem')->assertForbidden();
});

test('deleting a material requires a reason and moves it to the deleted log', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $s = aCatalogSupplier();
    $m = Material::create(['supplier_id' => $s->id, 'category' => 'X', 'description' => 'Gasket', 'material_nbr' => 'MN-9']);

    // reason is required
    Livewire::test(Index::class)
        ->call('openDelete', $m->id)
        ->call('deleteRecord')
        ->assertHasErrors('deleteReason');

    expect(Material::whereKey($m->id)->exists())->toBeTrue();

    // with a reason → soft-deleted + audit stored
    Livewire::test(Index::class)
        ->call('openDelete', $m->id)
        ->set('deleteReason', 'ຊ້ຳ ກັບ MN-8')
        ->call('deleteRecord')
        ->assertHasNoErrors();

    $m->refresh();
    expect($m->trashed())->toBeTrue();
    expect($m->deleted_reason)->toBe('ຊ້ຳ ກັບ MN-8');
    expect($m->deleted_by)->toBe($admin->id);

    // deleted log lists it + restore
    Livewire::test(Index::class)
        ->call('toggleDeleted')
        ->assertSee('Gasket')
        ->call('restore', $m->id);

    expect(Material::whereKey($m->id)->first()->trashed())->toBeFalse();
});

test('a supplier-scoped user cannot delete another supplier material', function () {
    $own = aCatalogSupplier('Own');
    $other = aCatalogSupplier('Other');
    $foreign = Material::create(['supplier_id' => $other->id, 'category' => 'X', 'description' => 'Foreign']);

    $sup = User::factory()->create(['is_super_admin' => false, 'supplier_id' => $own->id]);
    $sup->assignRole('supplier');
    $sup->givePermissionTo('catalog.delete');
    $this->actingAs($sup);

    Livewire::test(Index::class)
        ->call('openDelete', $foreign->id)
        ->assertForbidden();

    expect(Material::whereKey($foreign->id)->first()->trashed())->toBeFalse();
});
