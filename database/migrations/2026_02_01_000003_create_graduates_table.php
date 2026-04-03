<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Data Lulusan
        Schema::create('graduates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('certificate_number')->unique()->nullable(); // Nomor ijazah
            $table->string('skl_number')->unique()->nullable(); // Nomor SKL (Surat Keterangan Lulus)
            $table->date('graduation_date');
            $table->decimal('final_score', 5, 2)->nullable(); // Nilai akhir rata-rata
            $table->enum('graduation_status', ['lulus', 'tidak_lulus', 'pending'])->default('pending');
            $table->string('predicate')->nullable(); // Predikat: Sangat Baik, Baik, Cukup
            $table->text('achievements')->nullable(); // Prestasi selama sekolah
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id']);
        });

        // Surat Keterangan Lulus (SKL)
        Schema::create('graduation_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('graduate_id')->constrained()->cascadeOnDelete();
            $table->string('letter_number')->unique();
            $table->date('issue_date');
            $table->text('content')->nullable();
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_printed')->default(false);
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graduation_letters');
        Schema::dropIfExists('graduates');
    }
};
