<?php

use Illuminate\Support\Facades\Route;

/**
 * Finance Routes
 * For managing payments and financial reports
 */
Route::middleware(['auth', 'verified'])->prefix('finance')->group(function () {
    // Payments Management
    Route::livewire('/payments', 'pages::finance.payments')
        ->middleware('permission:finance.view')
        ->name('finance.payments');

    // Financial Reports
    Route::livewire('/reports', 'pages::finance.reports')
        ->middleware('permission:finance.view')
        ->name('finance.reports');
        
    Route::get('/reports/export/pdf', [\App\Http\Controllers\FinanceExportController::class, 'exportPdf'])
        ->middleware('permission:finance.view')
        ->name('finance.reports.export.pdf');
        
    Route::get('/reports/export/excel', [\App\Http\Controllers\FinanceExportController::class, 'exportExcel'])
        ->middleware('permission:finance.view')
        ->name('finance.reports.export.excel');
});
