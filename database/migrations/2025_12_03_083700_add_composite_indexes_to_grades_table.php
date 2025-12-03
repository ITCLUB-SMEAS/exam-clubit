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
        Schema::table('grades', function (Blueprint $table) {
            // Composite index for common query pattern: find grade by exam, session, student
            $table->index(['exam_id', 'exam_session_id', 'student_id'], 'grades_exam_session_student_idx');
            
            // Index for flagged grades queries
            $table->index('is_flagged');
            
            // Index for status filtering
            $table->index('status');
            
            // Index for attempt_status filtering
            $table->index('attempt_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropIndex('grades_exam_session_student_idx');
            $table->dropIndex(['is_flagged']);
            $table->dropIndex(['status']);
            $table->dropIndex(['attempt_status']);
        });
    }
};
