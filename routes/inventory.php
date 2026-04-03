<?php

use Illuminate\Support\Facades\Route;

/**
 * Inventory Routes
 * For managing inventory items and borrowings
 */
Route::middleware(['auth', 'verified'])->prefix('inventory')->group(function () {
    // Inventory Items Management
    Route::livewire('/items', 'pages::inventory.items')
        ->middleware('permission:inventory.view')
        ->name('inventory.items');

    // Borrowings Management
    Route::livewire('/borrowings', 'pages::inventory.borrowings')
        ->middleware('permission:inventory.view')
        ->name('inventory.borrowings');
});
