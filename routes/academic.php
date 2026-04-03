<?php

use App\Http\Controllers\ReportExportController;
use Illuminate\Support\Facades\Route;

/**
 * Academic Routes
 * For managing academic data: subjects, schedules, grades, exams
 */
Route::middleware(['auth', 'verified'])->prefix('academic')->group(function () {
    // Subjects Management
    Route::livewire('/subjects', 'pages::academic.subjects')
        ->middleware('permission:subjects.view')
        ->name('academic.subjects');

    // Schedules Management
    Route::livewire('/schedules', 'pages::academic.schedules')
        ->middleware('permission:schedule.view,schedule.view_own')
        ->name('academic.schedules');

    // Announcements
    Route::livewire('/announcements', 'pages::academic.announcements')
        ->middleware(['permission:announcements.view', 'role:admin,kepala_sekolah,guru'])
        ->name('academic.announcements');

    // Extracurriculars
    Route::livewire('/extracurriculars', 'pages::academic.extracurriculars')
        ->middleware('permission:extracurriculars.view')
        ->name('academic.extracurriculars');

});

/**
 * Letters/Correspondence Routes
 */
Route::middleware(['auth', 'verified'])->prefix('letters')->group(function () {
    // Main letters management (student-related letters)
    Route::livewire('/', 'pages::letters.index')
        ->middleware('permission:letters.view')
        ->name('letters.index');

    // Incoming Letters Agenda (Surat Masuk)
    Route::livewire('/incoming', 'pages::letters.incoming')
        ->middleware('permission:letters.view')
        ->name('letters.incoming');

    // Outgoing Letters Agenda (Surat Keluar)
    Route::livewire('/outgoing', 'pages::letters.outgoing')
        ->middleware('permission:letters.view')
        ->name('letters.outgoing');

    // Calling Letters (Surat Panggilan)
    Route::livewire('/calling-letters', 'pages::letters.calling-letters')
        ->middleware('permission:calling-letters.view')
        ->name('letters.calling-letters');

    // Letter Templates (Template Format Surat)
    Route::livewire('/templates', 'pages::letters.templates')
        ->middleware('permission:letters.view')
        ->name('letters.templates');

    // Letter Requests from Students (Permohonan Surat Siswa)
    Route::livewire('/requests', 'pages::letters.letter-requests')
        ->middleware('permission:letters.view')
        ->name('letters.requests');

    // Create/Edit student letters
    Route::livewire('/create', 'pages::letters.form')
        ->middleware('permission:letters.create')
        ->name('letters.create');

    Route::livewire('/{letter}/edit', 'pages::letters.form')
        ->middleware('permission:letters.edit')
        ->name('letters.edit');

    Route::livewire('/{letter}', 'pages::letters.show')
        ->middleware('permission:letters.view')
        ->name('letters.show');
});

/**
 * Reports Routes
 */
Route::middleware(['auth', 'verified'])->prefix('reports')->group(function () {
    Route::livewire('/', 'pages::reports.index')
        ->middleware('permission:reports.view')
        ->name('reports.index');

    Route::livewire('/students', 'pages::reports.students')
        ->middleware('permission:reports.students')
        ->name('reports.students');

    Route::livewire('/employees', 'pages::reports.employees')
        ->middleware('permission:reports.employees')
        ->name('reports.employees');

    Route::livewire('/attendance', 'pages::reports.attendance')
        ->middleware('permission:attendance.view_summary')
        ->name('reports.attendance');

    // Export Routes
    Route::get('/export/summary', [ReportExportController::class, 'summaryCsv'])
        ->middleware('permission:reports.export')
        ->name('reports.export.summary');

    Route::get('/export/students', [ReportExportController::class, 'studentsCsv'])
        ->middleware('permission:reports.export')
        ->name('reports.export.students');

    Route::get('/export/employees', [ReportExportController::class, 'employeesCsv'])
        ->middleware('permission:reports.export')
        ->name('reports.export.employees');

    Route::get('/export/attendance', [ReportExportController::class, 'attendanceCsv'])
        ->middleware('permission:reports.export')
        ->name('reports.export.attendance');
});
