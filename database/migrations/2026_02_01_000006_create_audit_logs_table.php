<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Audit Log untuk semua aktivitas sistem
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable(); // Backup nama user
            $table->string('action'); // create, update, delete, login, logout, export, print, approve, reject
            $table->string('model_type')->nullable(); // Nama model yang diubah
            $table->unsignedBigInteger('model_id')->nullable(); // ID record yang diubah
            $table->string('description'); // Deskripsi aksi
            $table->json('old_values')->nullable(); // Nilai sebelum perubahan
            $table->json('new_values')->nullable(); // Nilai setelah perubahan
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method')->nullable(); // GET, POST, PUT, DELETE
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });

        // User Activity Log (lebih spesifik untuk login/logout)
        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('activity', ['login', 'logout', 'failed_login', 'password_reset', 'password_change']);
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('location')->nullable(); // City, Country dari IP
            $table->boolean('is_successful')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
        Schema::dropIfExists('audit_logs');
    }
};
