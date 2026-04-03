<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('employees')->cascadeOnDelete();
            $table->unsignedTinyInteger('semester'); // 1 = Ganjil, 2 = Genap
            $table->string('grade_type', 30); // daily, assignment, quiz, midterm, final, practical, project
            $table->decimal('score', 5, 2);
            $table->text('description')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'subject_id', 'academic_year_id', 'semester'], 'grades_student_subject_idx');
            $table->index(['classroom_id', 'subject_id', 'grade_type']);
            $table->index(['teacher_id', 'academic_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
