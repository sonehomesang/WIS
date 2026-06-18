<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('inventory page renders global header: dynamic title + role badge + user menu', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    $this->get(route('inventory'))
        ->assertOk()
        ->assertSee('WH Inventories') // dynamic page title (left)
        ->assertSee('Super Admin')    // role badge (user menu)
        ->assertSee('Log Out');       // user dropdown
});

test('user menu appears on every page (dashboard too)', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Log Out');
});
