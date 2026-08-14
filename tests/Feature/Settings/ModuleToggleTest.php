<?php

use App\Livewire\Settings\System;
use App\Models\Setting;
use App\Models\User;
use App\Support\Modules;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('modules default to enabled and reflect the stored flag', function () {
    expect(Modules::enabled('expo'))->toBeTrue();       // unset → on
    expect(Modules::enabled('dashboard'))->toBeTrue();  // core → always on

    Setting::put('modules', ['expo' => false]);
    expect(Modules::enabled('expo'))->toBeFalse();
    expect(Modules::enabled('borrow'))->toBeTrue();     // still unset → on
});

test('a disabled module 404s and re-enabling restores access', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    $this->get('/expo')->assertOk();

    Setting::put('modules', ['expo' => false]);
    $this->get('/expo')->assertNotFound();

    Setting::put('modules', ['expo' => true]);
    $this->get('/expo')->assertOk();
});

test('core routes stay reachable regardless of the modules map', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    Setting::put('modules', ['expo' => false]);

    $this->get('/dashboard')->assertOk();
    $this->get('/inventory')->assertOk();
});

test('super admin can save module flags', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(System::class)
        ->set('modules.expo', false)
        ->set('modules.oga', false)
        ->call('saveModules')
        ->assertHasNoErrors();

    $mods = Setting::get('modules');
    expect($mods['expo'])->toBeFalse();
    expect($mods['oga'])->toBeFalse();
    expect($mods['borrow'])->toBeTrue();
});

test('super admin can configure borrow acknowledge/approve steps', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(System::class)
        ->set('borrowAcknowledge', 'required')
        ->set('borrowApprove', 'off')
        ->call('saveBorrowWorkflow')
        ->assertHasNoErrors();

    $wf = Setting::get('workflow')['borrow'];
    expect($wf['acknowledge'])->toBe('required');
    expect($wf['approve'])->toBe('off');
});

test('borrow workflow rejects an invalid step value', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(System::class)
        ->set('borrowApprove', 'maybe')
        ->call('saveBorrowWorkflow')
        ->assertHasErrors('borrowApprove');
});
