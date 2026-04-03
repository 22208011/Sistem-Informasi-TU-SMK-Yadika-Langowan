<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $classrooms = DB::table('classrooms')
            ->select('id', 'name', 'grade', 'academic_year_id')
            ->orderBy('id')
            ->get();

        foreach ($classrooms as $classroom) {
            $name = preg_replace('/\s+/', ' ', trim((string) $classroom->name));
            $baseName = preg_replace('/^(?:(?:XII|XI|X)\s+)+/i', '', $name);
            $baseName = trim((string) $baseName);

            $target = trim($classroom->grade.' '.$baseName);
            if ($baseName === '') {
                $target = (string) $classroom->grade;
            }

            // Enforce column limit with collision-safe suffix handling.
            $target = mb_substr($target, 0, 20);
            $candidate = $target;
            $suffix = 2;

            while (DB::table('classrooms')
                ->where('academic_year_id', $classroom->academic_year_id)
                ->where('name', $candidate)
                ->where('id', '<>', $classroom->id)
                ->exists()) {
                $suffixText = ' '.$suffix;
                $baseLimit = 20 - strlen($suffixText);
                $candidate = mb_substr($target, 0, max(1, $baseLimit)).$suffixText;
                $suffix++;
            }

            if ($candidate !== $classroom->name) {
                DB::table('classrooms')
                    ->where('id', $classroom->id)
                    ->update([
                        'name' => $candidate,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data normalization is intentionally irreversible.
    }
};
