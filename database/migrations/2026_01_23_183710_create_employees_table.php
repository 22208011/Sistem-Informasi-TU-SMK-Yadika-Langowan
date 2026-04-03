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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->nullable()->unique(); // NIP untuk PNS
            $table->string('nuptk')->nullable()->unique(); // NUPTK
            $table->string('name');
            $table->enum('gender', ['L', 'P']); // L = Laki-laki, P = Perempuan
            $table->string('place_of_birth')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('religion')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->enum('employee_status', ['pns', 'pppk', 'honorer', 'kontrak'])->default('honorer');
            $table->enum('employee_type', ['guru', 'tendik'])->default('guru'); // Guru / Tenaga Kependidikan
            $table->date('join_date')->nullable(); // Tanggal Mulai Bekerja
            $table->string('education_level')->nullable(); // S1, S2, S3, dll
            $table->string('education_major')->nullable(); // Jurusan Pendidikan
            $table->string('education_institution')->nullable(); // Nama Perguruan Tinggi
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete(); // Untuk Guru Produktif
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete(); // Link ke User Account
            $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
