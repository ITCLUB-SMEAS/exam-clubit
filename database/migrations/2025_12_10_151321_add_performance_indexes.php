<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Composite index for grades - common dashboard query
        $exists = DB::select("SHOW INDEX FROM grades WHERE Key_name = 'grades_student_created_idx'");
        if (empty($exists)) {
            Schema::table('grades', function (Blueprint $table) {
                $table->index(['student_id', 'created_at'], 'grades_student_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropIndex('grades_student_created_idx');
        });
    }
};
