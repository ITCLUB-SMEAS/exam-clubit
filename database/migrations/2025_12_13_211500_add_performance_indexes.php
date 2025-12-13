<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }

    public function up(): void
    {
        // Grades table
        Schema::table('grades', function (Blueprint $table) {
            if (!$this->indexExists('grades', 'grades_end_time_index')) {
                $table->index('end_time');
            }
            if (!$this->indexExists('grades', 'grades_start_time_index')) {
                $table->index('start_time');
            }
        });

        // Exam sessions - time-based queries
        if (!$this->indexExists('exam_sessions', 'exam_sessions_start_time_end_time_index')) {
            Schema::table('exam_sessions', function (Blueprint $table) {
                $table->index(['start_time', 'end_time']);
            });
        }

        // Students - classroom filter
        Schema::table('students', function (Blueprint $table) {
            if (!$this->indexExists('students', 'students_classroom_id_index')) {
                $table->index('classroom_id');
            }
            if (!$this->indexExists('students', 'students_room_id_index')) {
                $table->index('room_id');
            }
            if (!$this->indexExists('students', 'students_is_blocked_index')) {
                $table->index('is_blocked');
            }
        });

        // Answers - needs_manual_review for essay grading
        if (!$this->indexExists('answers', 'answers_needs_manual_review_index')) {
            Schema::table('answers', function (Blueprint $table) {
                $table->index('needs_manual_review');
            });
        }

        // Question bank - filtering
        Schema::table('question_banks', function (Blueprint $table) {
            if (!$this->indexExists('question_banks', 'question_banks_category_id_index')) {
                $table->index('category_id');
            }
            if (!$this->indexExists('question_banks', 'question_banks_question_type_index')) {
                $table->index('question_type');
            }
            if (!$this->indexExists('question_banks', 'question_banks_difficulty_index')) {
                $table->index('difficulty');
            }
        });
    }

    public function down(): void
    {
        // Indexes will be dropped if they exist
        Schema::table('grades', function (Blueprint $table) {
            $table->dropIndex(['end_time']);
            $table->dropIndex(['start_time']);
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropIndex(['start_time', 'end_time']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['classroom_id']);
            $table->dropIndex(['room_id']);
            $table->dropIndex(['is_blocked']);
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->dropIndex(['needs_manual_review']);
        });

        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropIndex(['question_type']);
            $table->dropIndex(['difficulty']);
        });
    }
};
