<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    // Self-registration is disabled — accounts come from the AD/HR sync +
    // admin (users:import-json). Re-enable only if open signup is wanted.
    // Volt::route('register', 'pages.auth.register')->name('register');

    Volt::route('login', 'pages.auth.login')
        ->name('login');

    // Local password set/reset — disabled when LDAP/AD takes over (LOCAL_AUTH=false).
    if (config('features.local_auth')) {
        Volt::route('forgot-password', 'pages.auth.forgot-password')
            ->middleware('throttle:6,1')
            ->name('password.request');

        Volt::route('reset-password/{token}', 'pages.auth.reset-password')
            ->middleware('throttle:6,1')
            ->name('password.reset');
    }
});

Route::middleware('auth')->group(function () {
    Volt::route('verify-email', 'pages.auth.verify-email')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Volt::route('confirm-password', 'pages.auth.confirm-password')
        ->name('password.confirm');
});
