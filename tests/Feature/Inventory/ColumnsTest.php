<?php

use App\Livewire\Inventory\Index;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
});

test('inventory list renders the column chooser + headers + persist binding', function () {
    Livewire::test(Index::class)
        ->assertSee('Columns')
        ->assertSee('Material No.')
        ->assertSee('Brand')
        ->assertSee('wh_inv_cols'); // Alpine $persist key (localStorage)
});
