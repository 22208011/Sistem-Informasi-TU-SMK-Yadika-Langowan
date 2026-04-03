<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Surat Panggilan (SP1, SP2, SP3)
        Schema::create('calling_letters', function (Blueprint $table) {
            $table->id();
            $table->string('letter_number')->unique();
            $table->enum('type', ['SP1', 'SP2', 'SP3']);
            $table->string('subject'); // Perihal
            $table->text('content')->nullable(); // Isi surat tambahan
            $table->date('letter_date');
            $table->date('meeting_date');
            $table->time('meeting_time');
            $table->string('meeting_place');
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['draft', 'sent', 'completed'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Siswa yang dipanggil dalam surat panggilan
        Schema::create('calling_letter_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calling_letter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->enum('attendance_status', ['pending', 'hadir', 'tidak_hadir'])->default('pending');
            $table->text('reason')->nullable(); // Alasan panggilan per siswa
            $table->text('result')->nullable(); // Hasil pertemuan
            $table->timestamps();

            $table->unique(['calling_letter_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calling_letter_students');
        Schema::dropIfExists('calling_letters');
    }
};
