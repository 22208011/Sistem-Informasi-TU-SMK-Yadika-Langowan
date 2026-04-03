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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('nis', 20)->unique(); // Nomor Induk Siswa
            $table->string('nisn', 20)->nullable()->unique(); // Nomor Induk Siswa Nasional
            $table->string('name');
            $table->enum('gender', ['L', 'P']);
            $table->string('place_of_birth')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('religion')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('previous_school')->nullable(); // Asal Sekolah
            $table->year('entry_year'); // Tahun Masuk
            $table->enum('status', ['aktif', 'lulus', 'pindah', 'keluar', 'do'])->default('aktif');
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete(); // Tahun Ajaran Masuk
            $table->string('photo')->nullable();
            $table->timestamps();

            $table->index('entry_year');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
