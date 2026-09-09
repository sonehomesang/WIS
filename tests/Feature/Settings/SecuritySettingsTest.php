<?php

use App\Livewire\Settings\System;
use App\Models\Setting;
use App\Models\User;
use App\Support\SecuritySettings;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

test('security settings save and are read back through the cached accessor', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(System::class)
        ->set('requireEmailVerification', true)
        ->set('idleTimeoutMinutes', 5)
        ->call('saveSecurity')
        ->assertHasNoErrors();

    $stored = Setting::get('security');
    expect($stored['require_email_verification'])->toBeTrue()
        ->and($stored['idle_timeout_minutes'])->toBe(5)
        ->and(SecuritySettings::verificationRequired())->toBeTrue()   // cache was cleared on save
        ->and(SecuritySettings::idleTimeoutMinutes())->toBe(5);
});

test('idle timeout rejects out-of-range values', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(System::class)
        ->set('idleTimeoutMinutes', -1)
        ->call('saveSecurity')
        ->assertHasErrors('idleTimeoutMinutes');
});

test('the accessor falls back to sensible defaults when nothing is stored', function () {
    SecuritySettings::forget();

    expect(SecuritySettings::verificationRequired())->toBeFalse()
        ->and(SecuritySettings::idleTimeoutMinutes())->toBe(3);
});
