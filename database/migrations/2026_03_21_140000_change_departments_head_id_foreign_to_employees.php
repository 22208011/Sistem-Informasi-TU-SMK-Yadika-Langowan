<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Map existing user-based head_id values to employees.id where possible.
        DB::table('departments')
            ->whereNotNull('head_id')
            ->update([
                'head_id' => DB::raw('(SELECT id FROM employees WHERE employees.user_id = departments.head_id)'),
            ]);

        // SQLite does not support dropping / re-adding foreign keys via ALTER TABLE.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['head_id']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('head_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore employees-based head_id values back to users.id where available.
        DB::table('departments')
            ->whereNotNull('head_id')
            ->update([
                'head_id' => DB::raw('(SELECT user_id FROM employees WHERE employees.id = departments.head_id)'),
            ]);

        // SQLite does not support dropping / re-adding foreign keys via ALTER TABLE.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['head_id']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('head_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
