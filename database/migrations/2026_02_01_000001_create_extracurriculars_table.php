<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ekstrakurikuler table
        Schema::create('extracurriculars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->text('description')->nullable();
            $table->enum('category', ['olahraga', 'seni', 'akademik', 'keagamaan', 'keterampilan', 'lainnya'])->default('lainnya');
            $table->string('schedule')->nullable(); // e.g., "Senin & Rabu 15:00-17:00"
            $table->string('location')->nullable();
            $table->foreignId('coach_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('max_members')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Anggota ekstrakurikuler
        Schema::create('extracurricular_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extracurricular_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['anggota', 'ketua', 'wakil_ketua', 'sekretaris', 'bendahara'])->default('anggota');
            $table->date('join_date')->nullable();
            $table->date('leave_date')->nullable();
            $table->enum('status', ['aktif', 'tidak_aktif', 'keluar'])->default('aktif');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['extracurricular_id', 'student_id', 'academic_year_id'], 'extracurricular_member_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extracurricular_members');
        Schema::dropIfExists('extracurriculars');
    }
};
