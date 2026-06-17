<?php

use App\Livewire\Settings\System;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('super admin can update global VAT', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(System::class)
        ->set('vat_rate', 7)
        ->set('vat_enabled', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('vat')['rate'])->toEqual(7.0);
});

test('non-permitted user cannot open system settings', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => false]));
    Livewire::test(System::class)->assertForbidden();
});
