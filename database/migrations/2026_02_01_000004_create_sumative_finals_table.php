<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nilai Sumatif Akhir (PAS/UAS) dengan approval workflow
        Schema::create('sumative_finals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->enum('semester', ['ganjil', 'genap']);
            $table->enum('type', ['PAS', 'PAT', 'UAS', 'UKK']); // Penilaian Akhir Semester, Penilaian Akhir Tahun, dll
            $table->decimal('score', 5, 2)->nullable();
            $table->text('notes')->nullable();
            
            // Workflow approval
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            $table->timestamps();

            $table->unique(['student_id', 'subject_id', 'academic_year_id', 'semester', 'type'], 'sumative_unique');
        });

        // Riwayat perubahan nilai sumatif
        Schema::create('sumative_final_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sumative_final_id')->constrained()->cascadeOnDelete();
            $table->decimal('old_score', 5, 2)->nullable();
            $table->decimal('new_score', 5, 2)->nullable();
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sumative_final_histories');
        Schema::dropIfExists('sumative_finals');
    }
};
