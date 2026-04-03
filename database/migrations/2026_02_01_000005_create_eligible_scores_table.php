<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nilai Eligible untuk tingkat akhir (input PT)
        Schema::create('eligible_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            
            // Komponen nilai
            $table->decimal('academic_score', 5, 2)->nullable(); // Nilai akademik (rapor)
            $table->decimal('sumative_score', 5, 2)->nullable(); // Nilai sumatif
            $table->decimal('exam_score', 5, 2)->nullable(); // Nilai ujian sekolah
            
            // Bobot perhitungan (dalam persen)
            $table->integer('academic_weight')->default(40);
            $table->integer('sumative_weight')->default(30);
            $table->integer('exam_weight')->default(30);
            
            // Nilai akhir eligible (dihitung)
            $table->decimal('final_eligible_score', 5, 2)->nullable();
            
            // Flag untuk data dari sekolah asal
            $table->boolean('is_from_previous_school')->default(false);
            $table->string('previous_school_name')->nullable();
            $table->text('previous_school_notes')->nullable();
            
            // Status kelengkapan data
            $table->boolean('is_complete')->default(false);
            $table->text('missing_data_notes')->nullable();
            
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            
            $table->timestamps();

            $table->unique(['student_id', 'subject_id', 'academic_year_id'], 'eligible_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eligible_scores');
    }
};
