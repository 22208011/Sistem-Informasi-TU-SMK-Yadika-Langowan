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
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->enum('relationship', ['ayah', 'ibu', 'wali']); // Hubungan dengan siswa
            $table->string('name');
            $table->string('nik', 20)->nullable(); // NIK KTP
            $table->string('place_of_birth')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('religion')->nullable();
            $table->string('education')->nullable(); // Pendidikan Terakhir
            $table->string('occupation')->nullable(); // Pekerjaan
            $table->string('income')->nullable(); // Penghasilan
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_primary')->default(false); // Wali utama
            $table->timestamps();

            $table->index(['student_id', 'relationship']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
