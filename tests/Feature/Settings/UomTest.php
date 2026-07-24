<?php

use App\Livewire\Settings\Uom;
use App\Models\Uom as UomModel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('super admin can create a uom', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(Uom::class)->call('newItem')->set('name', 'Kilogram')->call('save')->assertHasNoErrors();

    expect(UomModel::where('name', 'Kilogram')->exists())->toBeTrue();
});

test('duplicate uom name is rejected', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    UomModel::create(['slug' => 'kg', 'name' => 'kg', 'is_active' => true]);

    Livewire::test(Uom::class)->call('newItem')->set('name', 'kg')->call('save')->assertHasErrors(['name']);
});

test('non-permitted user cannot open uom', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => false]));
    Livewire::test(Uom::class)->assertForbidden();
});

test('deleting a uom requires a reason and can be restored', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $m = UomModel::create(['slug' => 'box', 'name' => 'ກ່ອງ', 'is_active' => true]);

    Livewire::test(Uom::class)
        ->call('openDelete', $m->id)
        ->call('deleteRecord')
        ->assertHasErrors('deleteReason');

    Livewire::test(Uom::class)
        ->call('openDelete', $m->id)
        ->set('deleteReason', 'ຊ້ຳ ກັບ pcs')
        ->call('deleteRecord')
        ->assertHasNoErrors();

    $m->refresh();
    expect($m->trashed())->toBeTrue();
    expect($m->deleted_reason)->toBe('ຊ້ຳ ກັບ pcs');
    expect($m->deleted_by)->toBe($admin->id);

    Livewire::test(Uom::class)
        ->call('toggleDeleted')
        ->assertSee('ກ່ອງ')
        ->call('restore', $m->id);

    expect(UomModel::whereKey($m->id)->first()->trashed())->toBeFalse();
});
