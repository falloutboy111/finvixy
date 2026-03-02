<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::livewire('email-otp/challenge', 'pages::auth.email-otp-challenge')->name('email-otp.challenge');
    Route::livewire('enable-email-2fa', 'pages::auth.enable-email-2fa')->name('email-2fa.enable');
});

Route::middleware(['auth', 'email.otp'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
    Route::livewire('expenses', 'pages::expenses.index')->name('expenses.index');
    Route::livewire('reports/spending', 'pages::reports.spending')->name('reports.spending');
    Route::livewire('reports/insights', 'pages::reports.insights')->name('reports.insights');
});

require __DIR__.'/settings.php';
