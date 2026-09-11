<?php

use App\Livewire\Inventory\Index;
use App\Models\InventoryItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    // out (qty<=0), low (0<qty<=min), ok (qty>min) — partition the set exactly
    InventoryItem::create(['slug' => 'A1', 'name' => 'Out item A', 'quantity' => 0, 'min_quantity' => 0]);    // out
    InventoryItem::create(['slug' => 'A2', 'name' => 'Out item D', 'quantity' => 0, 'min_quantity' => 10]);   // out
    InventoryItem::create(['slug' => 'B1', 'name' => 'Low item B', 'quantity' => 3, 'min_quantity' => 5]);    // low
    InventoryItem::create(['slug' => 'B2', 'name' => 'Low item E', 'quantity' => 5, 'min_quantity' => 5]);    // low (qty==min)
    InventoryItem::create(['slug' => 'C1', 'name' => 'Ok item C', 'quantity' => 10, 'min_quantity' => 2]);    // ok
});

test('header KPIs compute stock state from qty vs min_quantity', function () {
    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee('WH Inventory')          // header band
        ->assertSee('ໝົດ stock')             // KPI label
        ->assertViewHas('kpi', function ($k) {
            return $k['items'] === 5
                && $k['qty'] === 18           // 0+0+3+5+10
                && $k['out'] === 2
                && $k['low'] === 2
                && $k['ok'] === 1;
        });
});

test('stockFilter=out shows only qty≤0 items', function () {
    Livewire::test(Index::class)
        ->set('stockFilter', 'out')
        ->assertSee('Out item A')
        ->assertSee('Out item D')
        ->assertDontSee('Low item B')
        ->assertDontSee('Ok item C');
});

test('stockFilter=low shows only 0<qty≤min items', function () {
    Livewire::test(Index::class)
        ->set('stockFilter', 'low')
        ->assertSee('Low item B')
        ->assertSee('Low item E')
        ->assertDontSee('Out item A')
        ->assertDontSee('Ok item C');
});

test('stockFilter=ok shows only qty>min items', function () {
    Livewire::test(Index::class)
        ->set('stockFilter', 'ok')
        ->assertSee('Ok item C')
        ->assertDontSee('Out item A')
        ->assertDontSee('Low item B');
});

test('SECURITY — an unknown stockFilter value is ignored (no injection, shows all)', function () {
    Livewire::test(Index::class)
        ->set('stockFilter', "'; DROP TABLE inventory_items; --")
        ->assertOk()
        ->assertSee('Out item A')
        ->assertSee('Low item B')
        ->assertSee('Ok item C');   // no filter applied for a non-whitelisted value

    expect(InventoryItem::count())->toBe(5);   // table intact
});
