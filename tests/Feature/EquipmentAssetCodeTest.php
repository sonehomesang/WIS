<?php

use App\Livewire\Equipment\Index;
use App\Models\Equipment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->staff = User::factory()->create();
    $this->staff->syncRoles(['warehouse_staff']);
    actingAs($this->staff);
});

test('a duplicate asset code is rejected', function () {
    Equipment::create(['asset_code' => 'DUP-001', 'name' => 'A', 'quantity' => 1]);

    Livewire::test(Index::class)
        ->call('newItem')
        ->set('asset_code', 'DUP-001')
        ->set('name', 'B')
        ->set('quantity', 1)
        ->call('save')
        ->assertHasErrors(['asset_code' => 'unique']);
});

test('an asset code longer than 10 characters is rejected', function () {
    Livewire::test(Index::class)
        ->call('newItem')
        ->set('asset_code', '12345678901')   // 11 chars
        ->set('name', 'B')
        ->set('quantity', 1)
        ->call('save')
        ->assertHasErrors(['asset_code' => 'max']);
});

test('a 10-char code with letters and symbols is accepted', function () {
    Livewire::test(Index::class)
        ->call('newItem')
        ->set('asset_code', 'AB-12/CD.9')    // 10 chars, mixed
        ->set('name', 'B')
        ->set('quantity', 1)
        ->call('save')
        ->assertHasNoErrors();

    expect(Equipment::where('asset_code', 'AB-12/CD.9')->exists())->toBeTrue();
});
