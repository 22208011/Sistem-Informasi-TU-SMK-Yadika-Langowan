<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jenis Pembayaran
        Schema::create('payment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // SPP, Uang Gedung, Seragam, dll
            $table->string('code', 20)->unique();
            $table->text('description')->nullable();
            $table->decimal('default_amount', 15, 2)->default(0);
            $table->boolean('is_recurring')->default(false); // Apakah bayar rutin (bulanan)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Pembayaran
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->decimal('amount', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2); // amount - discount
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->enum('payment_status', ['belum_bayar', 'sebagian', 'lunas'])->default('belum_bayar');
            $table->date('due_date')->nullable();
            $table->integer('month')->nullable(); // Untuk pembayaran bulanan (1-12)
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Detail Pembayaran (history transaksi)
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('receipt_number')->unique();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->enum('payment_method', ['tunai', 'transfer', 'qris', 'lainnya'])->default('tunai');
            $table->string('reference_number')->nullable(); // No. Transfer/Ref
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_types');
    }
};
