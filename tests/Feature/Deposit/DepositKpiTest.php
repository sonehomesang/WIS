<?php

use App\Livewire\Deposit\Index;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
});

test('deposit index shows the KPI band and clamps a non-whitelisted perPage', function () {
    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee('ໃບ ຝາກ ທັງໝົດ')   // shared KPI band
        ->set('perPage', 999)              // non-whitelisted → clamped to 8
        ->assertViewHas('records', fn ($p) => $p->perPage() === 8);
});
