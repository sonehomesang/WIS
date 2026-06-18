<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('inventory', \App\Livewire\Inventory\Index::class)
    ->middleware(['auth', 'verified'])
    ->name('inventory');

Route::get('borrow', \App\Livewire\Borrow\Index::class)
    ->middleware(['auth', 'verified'])
    ->name('borrow');

Route::get('borrow/create', \App\Livewire\Borrow\Create::class)
    ->middleware(['auth', 'verified'])
    ->name('borrow.create');

Route::get('borrow/{record}', \App\Livewire\Borrow\Show::class)
    ->middleware(['auth', 'verified'])
    ->name('borrow.show');

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

Route::get('settings/uom', \App\Livewire\Settings\Uom::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.uom');

Route::get('settings/suppliers', \App\Livewire\Settings\Suppliers::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.suppliers');

Route::get('settings/suppliers/{supplier}', \App\Livewire\Settings\SupplierDetail::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.suppliers.show');

Route::get('settings/system', \App\Livewire\Settings\System::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.system');

require __DIR__.'/auth.php';
