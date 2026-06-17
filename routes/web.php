<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Settings hub — consolidates users/roles/org/uom/suppliers/audit/reports/notifications
Route::view('settings', 'settings.index')
    ->middleware(['auth', 'verified'])
    ->name('settings');

Route::get('settings/organization', \App\Livewire\Settings\Organization::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.organization');

Route::get('settings/users', \App\Livewire\Settings\Users::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.users');

Route::get('settings/roles', \App\Livewire\Settings\RolesPermissions::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.roles');

Route::get('settings/facilities', \App\Livewire\Settings\Facilities::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.facilities');

require __DIR__.'/auth.php';
