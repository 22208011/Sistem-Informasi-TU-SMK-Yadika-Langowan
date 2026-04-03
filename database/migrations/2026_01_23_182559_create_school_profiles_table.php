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
        Schema::create('school_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('npsn')->unique(); // Nomor Pokok Sekolah Nasional
            $table->string('name'); // Nama Sekolah
            $table->enum('status', ['negeri', 'swasta'])->default('negeri');
            $table->string('accreditation')->nullable(); // A, B, C
            $table->text('address');
            $table->string('village')->nullable(); // Kelurahan/Desa
            $table->string('district')->nullable(); // Kecamatan
            $table->string('city')->nullable(); // Kabupaten/Kota
            $table->string('province')->nullable(); // Provinsi
            $table->string('postal_code', 10)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('fax', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('principal_name')->nullable(); // Nama Kepala Sekolah
            $table->string('principal_nip')->nullable(); // NIP Kepala Sekolah
            $table->string('logo')->nullable(); // Path to logo file
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_profiles');
    }
};
