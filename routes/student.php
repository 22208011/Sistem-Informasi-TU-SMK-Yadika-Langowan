<?php

use Illuminate\Support\Facades\Route;

/**
 * Student Routes
 */
Route::middleware(['auth', 'verified'])->prefix('students')->group(function () {
    // Student List
    Route::livewire('/', 'pages::student.index')
        ->middleware('permission:students.view')
        ->name('students.index');

    // Attendance
    Route::livewire('/attendance', 'pages::student.attendance.index')
        ->middleware('permission:attendance.view')
        ->name('students.attendance.index');

    Route::livewire('/attendance/recap', 'pages::student.attendance.recap')
        ->middleware('permission:attendance.view')
        ->name('students.attendance.recap');

    // ===== Student Mutations =====
    // Mutation List
    Route::livewire('/mutations', 'pages::student.mutation.index')
        ->middleware('permission:students.view')
        ->name('students.mutations.index');

    // New Student Registration
    Route::livewire('/mutations/register', 'pages::student.mutation.register')
        ->middleware('permission:students.create')
        ->name('students.mutations.register');

    // Mutation In (Pindahan Masuk)
    Route::livewire('/mutations/in', 'pages::student.mutation.in')
        ->middleware('permission:students.create')
        ->name('students.mutations.in');

    // Mutation Out (Pindah Keluar/DO/Lulus)
    Route::livewire('/mutations/out', 'pages::student.mutation.out')
        ->middleware('permission:students.edit')
        ->name('students.mutations.out');

    // Class Promotion (Naik Kelas)
    Route::livewire('/mutations/promotion', 'pages::student.mutation.promotion')
        ->middleware('permission:students.edit')
        ->name('students.mutations.promotion');

    // Create Student
    Route::livewire('/create', 'pages::student.form')
        ->middleware('permission:students.create')
        ->name('students.create');

    // Graduates (must be before /{student} to avoid route conflict)
    Route::livewire('/graduates', 'pages::student.graduates')
        ->middleware('permission:graduates.view')
        ->name('students.graduates');

    // Edit Student
    Route::livewire('/{student}/edit', 'pages::student.form')
        ->middleware('permission:students.edit')
        ->name('students.edit');

    // View Student
    Route::livewire('/{student}', 'pages::student.show')
        ->middleware('permission:students.view')
        ->name('students.show');
});

/**
 * Guardian Routes
 */
Route::middleware(['auth', 'verified'])->prefix('guardians')->group(function () {
    // Guardian List
    Route::livewire('/', 'pages::student.guardians')
        ->middleware('permission:students.view')
        ->name('guardians.index');
});
