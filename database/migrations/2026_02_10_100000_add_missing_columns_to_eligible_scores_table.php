<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eligible_scores', function (Blueprint $table) {
            // Add semester scores columns
            $table->decimal('semester_1_score', 5, 2)->nullable()->after('academic_year_id');
            $table->decimal('semester_2_score', 5, 2)->nullable()->after('semester_1_score');
            $table->decimal('semester_3_score', 5, 2)->nullable()->after('semester_2_score');
            $table->decimal('semester_4_score', 5, 2)->nullable()->after('semester_3_score');
            $table->decimal('semester_5_score', 5, 2)->nullable()->after('semester_4_score');
            $table->decimal('semester_6_score', 5, 2)->nullable()->after('semester_5_score');

            // Add calculated scores
            $table->decimal('final_score', 5, 2)->nullable()->after('semester_6_score');
            $table->decimal('eligible_score', 5, 2)->nullable()->after('final_score');
            $table->string('grade', 2)->nullable()->after('eligible_score');

            // Add is_final flag
            $table->boolean('is_final')->default(false)->after('grade');

            // Add notes column
            $table->text('notes')->nullable()->after('is_final');
        });
    }

    public function down(): void
    {
        Schema::table('eligible_scores', function (Blueprint $table) {
            $table->dropColumn([
                'semester_1_score',
                'semester_2_score',
                'semester_3_score',
                'semester_4_score',
                'semester_5_score',
                'semester_6_score',
                'final_score',
                'eligible_score',
                'grade',
                'is_final',
                'notes',
            ]);
        });
    }
};
