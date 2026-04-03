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
        Schema::create('employee_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['hadir', 'izin', 'sakit', 'cuti', 'dinas_luar', 'alpha'])->default('hadir');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->unsignedSmallInteger('late_minutes')->default(0); // Menit keterlambatan
            $table->unsignedSmallInteger('early_leave_minutes')->default(0); // Menit pulang cepat
            $table->unsignedSmallInteger('overtime_minutes')->default(0); // Menit lembur
            $table->text('notes')->nullable(); // Keterangan
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Unique constraint: satu pegawai hanya bisa punya satu record per hari
            $table->unique(['employee_id', 'date']);
            // Index untuk pencarian berdasarkan tanggal
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_attendances');
    }
};
