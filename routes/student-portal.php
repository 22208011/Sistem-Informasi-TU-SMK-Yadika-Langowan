<?php

use Illuminate\Support\Facades\Route;

/**
 * Student Portal Routes
 * Only accessible by users with siswa role
 */
Route::middleware(['auth', 'verified', 'role:siswa'])->prefix('student-portal')->group(function () {
    // Student Dashboard
    Route::livewire('/', 'pages::student-portal.dashboard')
        ->name('student-portal.dashboard');

    // Letter Requests
    Route::livewire('/letter-requests', 'pages::student-portal.letter-requests')
        ->name('student-portal.letter-requests');

    Route::livewire('/letter-requests/create', 'pages::student-portal.letter-request-form')
        ->name('student-portal.letter-requests.create');

    // Announcements
    Route::livewire('/announcements', 'pages::student-portal.announcements')
        ->name('student-portal.announcements');
});
