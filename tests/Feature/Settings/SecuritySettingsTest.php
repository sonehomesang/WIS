<?php

use App\Livewire\Settings\System;
use App\Models\Setting;
use App\Models\User;
use App\Support\SecuritySettings;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

test('idle timeout saves and is read back through the cached accessor', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(System::class)
        ->set('idleTimeoutMinutes', 5)
        ->call('saveSecurity')
        ->assertHasNoErrors();

    expect(Setting::get('security')['idle_timeout_minutes'])->toBe(5)
        ->and(SecuritySettings::idleTimeoutMinutes())->toBe(5);   // cache cleared on save
});

test('idle timeout rejects out-of-range values', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(System::class)
        ->set('idleTimeoutMinutes', -1)
        ->call('saveSecurity')
        ->assertHasErrors('idleTimeoutMinutes');
});

test('the accessor falls back to the default when nothing is stored', function () {
    SecuritySettings::forget();

    expect(SecuritySettings::idleTimeoutMinutes())->toBe(3);
});
