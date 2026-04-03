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
        Schema::create('student_mutations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');

            // Mutation type: masuk, keluar, pindah_kelas, naik_kelas, lulus, do
            $table->string('type', 20);

            // Dates
            $table->date('mutation_date');
            $table->date('effective_date')->nullable();

            // Details
            $table->string('reason')->nullable();
            $table->string('previous_school')->nullable(); // For incoming transfer students
            $table->string('destination_school')->nullable(); // For outgoing students

            // Classroom changes
            $table->foreignId('previous_classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->foreignId('new_classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();

            // Document
            $table->string('document_number')->nullable();
            $table->text('notes')->nullable();

            // Approval
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // Academic Year
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();

            // Indexes
            $table->index(['student_id', 'type']);
            $table->index(['type', 'status']);
            $table->index('mutation_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_mutations');
    }
};
