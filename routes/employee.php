<?php

use Illuminate\Support\Facades\Route;

/**
 * Employee Routes
 */
Route::middleware(['auth', 'verified'])->prefix('employees')->group(function () {
    // Employee List
    Route::livewire('/', 'pages::employee.index')
        ->middleware('permission:employees.view')
        ->name('employees.index');

    // Positions (harus sebelum route dengan parameter)
    Route::livewire('/positions', 'pages::employee.positions')
        ->middleware('permission:employees.view')
        ->name('employees.positions');

    // Attendance
    Route::livewire('/attendance', 'pages::employee.attendance.index')
        ->middleware('permission:attendance.view')
        ->name('employees.attendance.index');

    Route::livewire('/attendance/recap', 'pages::employee.attendance.recap')
        ->middleware('permission:attendance.view')
        ->name('employees.attendance.recap');

    // Create Employee
    Route::livewire('/create', 'pages::employee.form')
        ->middleware('permission:employees.create')
        ->name('employees.create');

    // Edit Employee
    Route::livewire('/{employee}/edit', 'pages::employee.form')
        ->middleware('permission:employees.edit')
        ->name('employees.edit');

    // View Employee (harus paling akhir)
    Route::livewire('/{employee}', 'pages::employee.show')
        ->middleware('permission:employees.view')
        ->name('employees.show');
});
