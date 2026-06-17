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
