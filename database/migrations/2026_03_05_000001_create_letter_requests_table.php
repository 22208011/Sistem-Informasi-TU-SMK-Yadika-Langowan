<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 50)->unique();
            $table->string('letter_type', 30); // active_student, internship, good_behavior, transfer
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('purpose')->nullable(); // Tujuan/Keperluan
            $table->text('notes')->nullable(); // Catatan tambahan dari siswa
            $table->string('attachment')->nullable(); // Dokumen pendukung
            $table->string('status', 20)->default('pending'); // pending, processing, completed, rejected
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_notes')->nullable(); // Catatan dari admin
            $table->string('result_file')->nullable(); // File surat hasil
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('letter_type');
        });

        // Add student_id to users table for student login
        if (! Schema::hasColumn('users', 'student_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('student_id')->nullable()->after('employee_id')->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_requests');

        if (Schema::hasColumn('users', 'student_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['student_id']);
                $table->dropColumn('student_id');
            });
        }
    }
};
