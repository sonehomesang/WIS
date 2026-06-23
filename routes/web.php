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

Route::get('dashboard', App\Livewire\Dashboard::class)
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

// ── Shops Material (catalog ສິນຄ້າ supplier) — Phase 6.7a ──
Route::get('catalog', App\Livewire\Catalog\Index::class)
    ->middleware(['auth', 'verified'])
    ->name('catalog');

// ── Request Material (ໃບເບີກວັດສະດຸ) — Phase 6.7b ──
Route::get('request', App\Livewire\Request\Index::class)
    ->middleware(['auth', 'verified'])
    ->name('request');

Route::get('request/create', App\Livewire\Request\Create::class)
    ->middleware(['auth', 'verified'])
    ->name('request.create');

Route::get('request/{record}/pdf', function (App\Models\MaterialRequest $record) {
    abort_unless(auth()->user()->can('request.view'), 403);
    $record->load(['items', 'supplier', 'unit', 'department', 'history']);

    return Pdf::loadView('request.pdf', ['record' => $record])
        ->download("request-{$record->request_number}.pdf");
})->middleware(['auth', 'verified'])->name('request.pdf');

Route::get('request/{record}', App\Livewire\Request\Show::class)
    ->middleware(['auth', 'verified'])
    ->name('request.show');

// ── DA Claims (Discrepancy Advice) — Phase 6.8b ──
Route::get('da', App\Livewire\Da\Index::class)
    ->middleware(['auth', 'verified'])
    ->name('da');

Route::get('da/create', App\Livewire\Da\Create::class)
    ->middleware(['auth', 'verified'])
    ->name('da.create');

Route::get('da/{record}/pdf', function (App\Models\DiscrepancyAdvice $record) {
    abort_unless(auth()->user()->can('da.view'), 403);
    $record->load(['items', 'photos', 'supplier', 'history']);

    return Pdf::loadView('da.pdf', ['record' => $record])
        ->download("da-{$record->da_number}.pdf");
})->middleware(['auth', 'verified'])->name('da.pdf');

Route::get('da/{record}', App\Livewire\Da\Show::class)
    ->middleware(['auth', 'verified'])
    ->name('da.show');

// ── OGA (Outwards Goods Advice) — Phase 6.8c ──
Route::get('oga', App\Livewire\Oga\Index::class)
    ->middleware(['auth', 'verified'])
    ->name('oga');

Route::get('oga/create', App\Livewire\Oga\Create::class)
    ->middleware(['auth', 'verified'])
    ->name('oga.create');

Route::get('oga/{record}/pdf', function (App\Models\OutwardsGoodsAdvice $record) {
    abort_unless(auth()->user()->can('oga.view'), 403);
    $record->load(['items', 'photos', 'supplier', 'history']);

    return Pdf::loadView('oga.pdf', ['record' => $record])
        ->download("oga-{$record->oga_number}.pdf");
})->middleware(['auth', 'verified'])->name('oga.pdf');

Route::get('oga/{record}', App\Livewire\Oga\Show::class)
    ->middleware(['auth', 'verified'])
    ->name('oga.show');

// ── Expo Info (mini-CRM) — Phase 6.9 ──
Route::get('expo', App\Livewire\Expo\Index::class)
    ->middleware(['auth', 'verified'])
    ->name('expo');

Route::get('expo/create', App\Livewire\Expo\Create::class)
    ->middleware(['auth', 'verified'])
    ->name('expo.create');

Route::get('expo/{record}/pdf', function (App\Models\ExpoEvent $record) {
    abort_unless(auth()->user()->can('expo.view'), 403);
    $record->load(['attendees', 'companies.contacts', 'companies.files']);

    return Pdf::loadView('expo.pdf', ['record' => $record])
        ->download("expo-{$record->expo_number}.pdf");
})->middleware(['auth', 'verified'])->name('expo.pdf');

Route::get('expo/{record}', App\Livewire\Expo\Show::class)
    ->middleware(['auth', 'verified'])
    ->name('expo.show');

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
