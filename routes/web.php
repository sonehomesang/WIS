<?php

use App\Livewire\Borrow\Create;
use App\Livewire\Borrow\Show;
use App\Livewire\Inventory\Index;
use App\Livewire\Settings\Facilities;
use App\Livewire\Settings\Organization;
use App\Livewire\Settings\RolesPermissions;
use App\Livewire\Settings\SupplierDetail;
use App\Livewire\Settings\Suppliers;
use App\Livewire\Settings\System;
use App\Livewire\Settings\Uom;
use App\Livewire\Settings\Users;
use App\Models\BorrowRecord;
use App\Models\DepositRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('inventory', Index::class)
    ->middleware(['auth', 'verified'])
    ->name('inventory');

Route::get('borrow', App\Livewire\Borrow\Index::class)
    ->middleware(['auth', 'verified'])
    ->name('borrow');

Route::get('borrow/create', Create::class)
    ->middleware(['auth', 'verified'])
    ->name('borrow.create');

Route::get('borrow/{record}/pdf', function (BorrowRecord $record) {
    abort_unless(auth()->user()->can('borrow.view'), 403);
    $record->load(['items.inventoryItem.primaryPhoto', 'items.photos', 'unit', 'department']);

    return Pdf::loadView('borrow.pdf', ['record' => $record])
        ->download("borrow-{$record->request_number}.pdf");
})->middleware(['auth', 'verified'])->name('borrow.pdf');

Route::get('borrow/{record}', Show::class)
    ->middleware(['auth', 'verified'])
    ->name('borrow.show');

// ── Deposit (ການຝາກເຄື່ອງ) — Phase 6.8a ──
Route::get('deposit', App\Livewire\Deposit\Index::class)
    ->middleware(['auth', 'verified'])
    ->name('deposit');

Route::get('deposit/create', App\Livewire\Deposit\Create::class)
    ->middleware(['auth', 'verified'])
    ->name('deposit.create');

Route::get('deposit/{record}/pdf', function (DepositRecord $record) {
    abort_unless(auth()->user()->can('deposit.view'), 403);
    $record->load(['items.photos', 'unit', 'department', 'history']);

    return Pdf::loadView('deposit.pdf', ['record' => $record])
        ->download("deposit-{$record->request_number}.pdf");
})->middleware(['auth', 'verified'])->name('deposit.pdf');

Route::get('deposit/{record}', App\Livewire\Deposit\Show::class)
    ->middleware(['auth', 'verified'])
    ->name('deposit.show');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Settings hub — consolidates users/roles/org/uom/suppliers/audit/reports/notifications
Route::view('settings', 'settings.index')
    ->middleware(['auth', 'verified'])
    ->name('settings');

Route::get('settings/organization', Organization::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.organization');

Route::get('settings/users', Users::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.users');

Route::get('settings/roles', RolesPermissions::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.roles');

Route::get('settings/facilities', Facilities::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.facilities');

Route::get('settings/uom', Uom::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.uom');

Route::get('settings/suppliers', Suppliers::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.suppliers');

Route::get('settings/suppliers/{supplier}', SupplierDetail::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.suppliers.show');

Route::get('settings/system', System::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.system');

require __DIR__.'/auth.php';
