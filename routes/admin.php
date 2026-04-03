<?php

use Illuminate\Support\Facades\Route;

/**
 * Admin Routes
 * Only accessible by users with admin role or users.* / roles.* permissions
 */
Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    // User Management
    Route::livewire('users', 'pages::admin.users')
        ->middleware('permission:users.view')
        ->name('admin.users');

    // Role Management
    Route::livewire('roles', 'pages::admin.roles')
        ->middleware('permission:roles.view')
        ->name('admin.roles');

    // Audit Logs
    Route::livewire('audit-logs', 'pages::admin.audit-logs')
        ->middleware('permission:audit-logs.view')
        ->name('admin.audit-logs');
});
