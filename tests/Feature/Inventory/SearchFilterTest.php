<?php

use App\Livewire\Inventory\Index;
use App\Models\InventoryItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    InventoryItem::create(['slug' => '23001', 'name' => 'Alpha valve', 'description' => 'red widget']);
    InventoryItem::create(['slug' => '23002', 'name' => 'Beta valve']);
    InventoryItem::create(['slug' => '31001', 'name' => 'Gamma pump']);
});

test('search matches Material Number (slug)', function () {
    Livewire::test(Index::class)
        ->set('search', '23001')
        ->assertSee('Alpha valve')
        ->assertDontSee('Gamma pump');
});

test('search matches description', function () {
    Livewire::test(Index::class)
        ->set('search', 'red widget')
        ->assertSee('Alpha valve')
        ->assertDontSee('Beta valve');
});

test('prefix filter narrows by Material No. group', function () {
    Livewire::test(Index::class)
        ->set('prefixFilter', '31')
        ->assertSee('Gamma pump')
        ->assertDontSee('Alpha valve');
});

test('prefixCounts groups by 2-digit Material No. prefix', function () {
    $counts = InventoryItem::prefixCounts()->keyBy('prefix');

    expect((int) $counts['23']->total)->toBe(2);
    expect((int) $counts['31']->total)->toBe(1);
});
