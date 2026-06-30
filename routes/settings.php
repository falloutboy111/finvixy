<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Laravel\Paddle\Transaction;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
    Route::livewire('settings/connected-accounts', 'pages::settings.connected-accounts')->name('connected-accounts.edit');
    Route::livewire('settings/categories', 'pages::settings.categories')->name('categories.edit');
    Route::livewire('settings/billing', 'pages::settings.billing')->name('billing');
    Route::livewire('settings/usage', 'pages::settings.usage')->name('usage');
    Route::livewire('settings/crm', 'pages::settings.crm')->name('crm.settings');

    Route::get('billing/invoice/{transaction}', function (Transaction $transaction) {
        return $transaction->redirectToInvoicePdf();
    })->name('billing.invoice');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/password', 'pages::settings.password')->name('user-password.edit');
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    Route::livewire('settings/two-factor', 'pages::settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});

// Google OAuth callback — no auth middleware, state carries user_id
Route::get('auth/google/callback', \App\Http\Controllers\GoogleCallbackController::class)
    ->name('google.callback');

// Xero OAuth routes — auth middleware protects all three
Route::middleware(['auth'])->group(function () {
    Route::get('xero/connect', [\App\Http\Controllers\XeroAuthController::class, 'redirect'])->name('xero.connect');
    Route::get('xero/callback', [\App\Http\Controllers\XeroAuthController::class, 'callback'])->name('xero.callback');
    Route::post('xero/disconnect', [\App\Http\Controllers\XeroAuthController::class, 'disconnect'])->name('xero.disconnect');
});
