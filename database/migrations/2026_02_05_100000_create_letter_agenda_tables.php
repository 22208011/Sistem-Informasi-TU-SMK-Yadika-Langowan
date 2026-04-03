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
        // Table untuk Agenda Surat Masuk
        Schema::create('incoming_letters', function (Blueprint $table) {
            $table->id();
            $table->string('agenda_number')->unique();
            $table->string('letter_number');
            $table->date('letter_date');
            $table->date('received_date');
            $table->string('sender');
            $table->text('sender_address')->nullable();
            $table->string('subject');
            $table->string('classification')->default('lainnya');
            $table->string('nature')->default('biasa');
            $table->integer('attachment_count')->default(0);
            $table->string('attachment_type')->nullable();
            $table->text('disposition')->nullable();
            $table->string('disposition_to')->nullable();
            $table->text('notes')->nullable();
            $table->string('file_path')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('received');
            $table->timestamps();

            // Indexes
            $table->index('letter_date');
            $table->index('received_date');
            $table->index('classification');
            $table->index('status');
        });

        // Table untuk Agenda Surat Keluar
        Schema::create('outgoing_letters', function (Blueprint $table) {
            $table->id();
            $table->string('agenda_number')->unique();
            $table->string('letter_number')->unique();
            $table->date('letter_date');
            $table->date('sent_date')->nullable();
            $table->string('recipient');
            $table->text('recipient_address')->nullable();
            $table->string('subject');
            $table->string('classification')->default('lainnya');
            $table->string('nature')->default('biasa');
            $table->integer('attachment_count')->default(0);
            $table->string('attachment_type')->nullable();
            $table->text('content_summary')->nullable();
            $table->text('notes')->nullable();
            $table->string('file_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->timestamps();

            // Indexes
            $table->index('letter_date');
            $table->index('sent_date');
            $table->index('classification');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outgoing_letters');
        Schema::dropIfExists('incoming_letters');
    }
};
