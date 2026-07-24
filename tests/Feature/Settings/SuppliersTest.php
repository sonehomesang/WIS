<?php

use App\Livewire\Settings\Suppliers;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('super admin can create a supplier', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(Suppliers::class)
        ->call('newItem')
        ->set('name', 'ABC Trading')
        ->set('default_currency', 'THB')
        ->call('save')
        ->assertHasNoErrors();

    expect(Supplier::where('name', 'ABC Trading')->first()->default_currency)->toBe('THB');
});

test('duplicate supplier name is rejected', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    Supplier::create(['slug' => 'abc', 'name' => 'ABC', 'default_currency' => 'LAK', 'is_active' => true]);

    Livewire::test(Suppliers::class)->call('newItem')->set('name', 'ABC')->call('save')->assertHasErrors(['name']);
});

test('non-permitted user cannot open suppliers', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => false]));
    Livewire::test(Suppliers::class)->assertForbidden();
});

test('deleting a supplier requires a reason and moves it to the deleted log', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $s = Supplier::create(['slug' => 'zed', 'name' => 'Zed Co', 'default_currency' => 'LAK', 'is_active' => true]);

    Livewire::test(Suppliers::class)
        ->call('openDelete', $s->id)
        ->call('deleteRecord')
        ->assertHasErrors('deleteReason');

    expect(Supplier::whereKey($s->id)->exists())->toBeTrue();

    Livewire::test(Suppliers::class)
        ->call('openDelete', $s->id)
        ->set('deleteReason', 'ບໍ່ ຮ່ວມ ງານ ແລ້ວ')
        ->call('deleteRecord')
        ->assertHasNoErrors();

    $s->refresh();
    expect($s->trashed())->toBeTrue();
    expect($s->deleted_reason)->toBe('ບໍ່ ຮ່ວມ ງານ ແລ້ວ');
    expect($s->deleted_by)->toBe($admin->id);

    Livewire::test(Suppliers::class)
        ->call('toggleDeleted')
        ->assertSee('Zed Co')
        ->call('restore', $s->id);

    expect(Supplier::whereKey($s->id)->first()->trashed())->toBeFalse();
});
