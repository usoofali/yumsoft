<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
    // Add these routes:
    Volt::route('products', 'products.index')->name('products.index');
    Volt::route('stocks', 'stocks.index')->name('stocks.index');
    Volt::route('shops', 'shops.index')->name('shops.index');
    Volt::route('users', 'users.index')->name('users.index');
    
    Volt::route('sales', 'sales.index')->name('sales.index');
    Volt::route('supplies', 'supplies.index')->name('supplies.index');
    Volt::route('suppliers', 'suppliers.index')->name('suppliers.index');
    Volt::route('customers', 'customers.index')->name('customers.index');
    Volt::route('reports', 'reports.index')->name('reports.index');
});

require __DIR__.'/auth.php';
