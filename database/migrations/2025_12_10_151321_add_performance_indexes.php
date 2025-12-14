<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if index already exists (safe for re-runs)
        try {
            Schema::table('grades', function (Blueprint $table) {
                $table->index(['student_id', 'created_at'], 'grades_student_created_idx');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }
    }

    public function down(): void
    {
        try {
            Schema::table('grades', function (Blueprint $table) {
                $table->dropIndex('grades_student_created_idx');
            });
        } catch (\Exception $e) {
            // Index doesn't exist, skip
        }
    }
};
