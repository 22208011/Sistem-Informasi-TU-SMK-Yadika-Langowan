<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        foreach (Schema::getIndexes($table) as $index) {
            if (isset($index['name']) && strcasecmp((string) $index['name'], $indexName) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Safely add index only if both index doesn't exist and all columns exist
     */
    private function safeAddIndex(string $table, array $columns, string $indexName): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        // Check if index already exists
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        // Check if all columns exist
        $existingColumns = Schema::getColumnListing($table);
        foreach ($columns as $column) {
            if (! in_array($column, $existingColumns, true)) {
                return; // Skip if any column doesn't exist
            }
        }

        // Add the index
        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    /**
     * Run the migrations.
     * Add indexes to improve query performance on frequently accessed columns.
     */
    public function up(): void
    {
        // Students table indexes
        $this->safeAddIndex('students', ['status', 'classroom_id'], 'students_status_classroom_index');
        $this->safeAddIndex('students', ['department_id', 'entry_year'], 'students_department_entry_index');

        // Employees table indexes
        $this->safeAddIndex('employees', ['employee_type', 'is_active'], 'employees_type_status_index');

        // Student attendances indexes
        $this->safeAddIndex('student_attendances', ['student_id', 'date'], 'student_attendances_student_date_index');
        $this->safeAddIndex('student_attendances', ['classroom_id', 'date'], 'student_attendances_classroom_date_index');

        // Employee attendances indexes
        $this->safeAddIndex('employee_attendances', ['employee_id', 'date'], 'employee_attendances_employee_date_index');

        // Grades table indexes
        $this->safeAddIndex('grades', ['student_id', 'subject_id', 'academic_year_id'], 'grades_student_subject_year_index');

        // Exam scores indexes
        $this->safeAddIndex('exam_scores', ['exam_id', 'student_id'], 'exam_scores_exam_student_index');

        // Schedules indexes
        $this->safeAddIndex('schedules', ['classroom_id', 'day_of_week', 'academic_year_id'], 'schedules_classroom_day_year_index');

        // Announcements indexes - use actual column names
        $this->safeAddIndex('announcements', ['is_active', 'is_pinned', 'published_at'], 'announcements_active_pinned_date_index');

        // Audit logs indexes
        $this->safeAddIndex('audit_logs', ['auditable_type', 'auditable_id'], 'audit_logs_auditable_index');
        $this->safeAddIndex('audit_logs', ['user_id', 'created_at'], 'audit_logs_user_created_index');
    }

    /**
     * Safely drop index only if it exists
     */
    private function safeDropIndex(string $table, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
                $blueprint->dropIndex($indexName);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->safeDropIndex('students', 'students_status_classroom_index');
        $this->safeDropIndex('students', 'students_department_entry_index');
        $this->safeDropIndex('employees', 'employees_type_status_index');
        $this->safeDropIndex('student_attendances', 'student_attendances_student_date_index');
        $this->safeDropIndex('student_attendances', 'student_attendances_classroom_date_index');
        $this->safeDropIndex('employee_attendances', 'employee_attendances_employee_date_index');
        $this->safeDropIndex('grades', 'grades_student_subject_year_index');
        $this->safeDropIndex('exam_scores', 'exam_scores_exam_student_index');
        $this->safeDropIndex('schedules', 'schedules_classroom_day_year_index');
        $this->safeDropIndex('announcements', 'announcements_active_pinned_date_index');
        $this->safeDropIndex('audit_logs', 'audit_logs_auditable_index');
        $this->safeDropIndex('audit_logs', 'audit_logs_user_created_index');
    }
};
