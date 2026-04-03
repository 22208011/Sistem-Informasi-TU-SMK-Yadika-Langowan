<?php

use Illuminate\Support\Facades\Route;

/**
 * Parent Portal Routes
 * Only accessible by users with orang_tua role
 */
Route::middleware(['auth', 'verified', 'role:orang_tua'])->prefix('parent')->group(function () {
    // Parent Dashboard
    Route::livewire('/', 'pages::parent.dashboard')
        ->name('parent.dashboard');

    // View Letters from School
    Route::livewire('/letters', 'pages::parent.letters')
        ->name('parent.letters');

    // View Announcements
    Route::livewire('/announcements', 'pages::parent.announcements')
        ->name('parent.announcements');

    // View Child's Attendance
    Route::livewire('/attendance', 'pages::parent.attendance')
        ->name('parent.attendance');
});
