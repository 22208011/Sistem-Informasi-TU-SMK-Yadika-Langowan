<?php

use Illuminate\Support\Facades\Route;

/**
 * Master Data Routes
 */
Route::middleware(['auth', 'verified'])->prefix('master')->group(function () {
    // School Profile
    Route::livewire('school-profile', 'pages::master.school-profile')
        ->middleware('permission:master.view,master.edit')
        ->name('master.school-profile');

    // Academic Years
    Route::livewire('academic-years', 'pages::master.academic-years')
        ->middleware('permission:master.view')
        ->name('master.academic-years');

    // Departments (Jurusan)
    Route::livewire('departments', 'pages::master.departments')
        ->middleware('permission:master.view')
        ->name('master.departments');

    // Classrooms (Kelas)
    Route::livewire('classrooms', 'pages::master.classrooms')
        ->middleware('permission:master.view')
        ->name('master.classrooms');
});
