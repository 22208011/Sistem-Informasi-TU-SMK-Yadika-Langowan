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
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20); // Nama Kelas (X RPL 1, XI TKJ 2)
            $table->enum('grade', ['X', 'XI', 'XII']); // Tingkat
            $table->foreignId('department_id')->constrained()->cascadeOnDelete(); // Jurusan
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete(); // Tahun Ajaran
            $table->foreignId('homeroom_teacher_id')->nullable()->constrained('users')->nullOnDelete(); // Wali Kelas
            $table->integer('capacity')->default(36); // Kapasitas Kelas
            $table->string('room')->nullable(); // Ruangan
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['name', 'academic_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
