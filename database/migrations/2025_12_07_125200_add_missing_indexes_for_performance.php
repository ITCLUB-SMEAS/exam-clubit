<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Students table
        Schema::table('students', function (Blueprint $table) {
            if (!$this->indexExists('students', 'students_nisn_index')) {
                $table->index('nisn');
            }
            if (!$this->indexExists('students', 'students_classroom_id_index')) {
                $table->index('classroom_id');
            }
            if (!$this->indexExists('students', 'students_room_id_index')) {
                $table->index('room_id');
            }
            if (!$this->indexExists('students', 'students_is_blocked_classroom_id_index')) {
                $table->index(['is_blocked', 'classroom_id']);
            }
        });

        // Grades table
        Schema::table('grades', function (Blueprint $table) {
            if (!$this->indexExists('grades', 'grades_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('grades', 'grades_exam_id_status_index')) {
                $table->index(['exam_id', 'status']);
            }
            if (!$this->indexExists('grades', 'grades_exam_session_id_status_index')) {
                $table->index(['exam_session_id', 'status']);
            }
            if (!$this->indexExists('grades', 'grades_student_id_exam_id_index')) {
                $table->index(['student_id', 'exam_id']);
            }
            if (!$this->indexExists('grades', 'grades_is_flagged_index')) {
                $table->index('is_flagged');
            }
            if (!$this->indexExists('grades', 'grades_is_paused_index')) {
                $table->index('is_paused');
            }
        });

        // Answers table
        Schema::table('answers', function (Blueprint $table) {
            if (!$this->indexExists('answers', 'answers_question_id_index')) {
                $table->index('question_id');
            }
            if (!$this->indexExists('answers', 'answers_needs_manual_review_index')) {
                $table->index('needs_manual_review');
            }
            if (!$this->indexExists('answers', 'answers_exam_id_student_id_index')) {
                $table->index(['exam_id', 'student_id']);
            }
        });

        // Questions table
        Schema::table('questions', function (Blueprint $table) {
            if (!$this->indexExists('questions', 'questions_exam_id_index')) {
                $table->index('exam_id');
            }
            if (!$this->indexExists('questions', 'questions_question_type_index')) {
                $table->index('question_type');
            }
        });

        // Exam violations table
        Schema::table('exam_violations', function (Blueprint $table) {
            if (!$this->indexExists('exam_violations', 'exam_violations_violation_type_index')) {
                $table->index('violation_type');
            }
            if (!$this->indexExists('exam_violations', 'exam_violations_exam_id_exam_session_id_index')) {
                $table->index(['exam_id', 'exam_session_id']);
            }
            if (!$this->indexExists('exam_violations', 'exam_violations_student_id_created_at_index')) {
                $table->index(['student_id', 'created_at']);
            }
        });

        // Exam sessions table
        Schema::table('exam_sessions', function (Blueprint $table) {
            if (!$this->indexExists('exam_sessions', 'exam_sessions_exam_id_index')) {
                $table->index('exam_id');
            }
            if (!$this->indexExists('exam_sessions', 'exam_sessions_start_time_end_time_index')) {
                $table->index(['start_time', 'end_time']);
            }
        });

        // Exam groups table
        Schema::table('exam_groups', function (Blueprint $table) {
            if (!$this->indexExists('exam_groups', 'exam_groups_checked_in_at_index')) {
                $table->index('checked_in_at');
            }
        });

        // Activity logs table
        Schema::table('activity_logs', function (Blueprint $table) {
            if (!$this->indexExists('activity_logs', 'activity_logs_action_index')) {
                $table->index('action');
            }
            if (!$this->indexExists('activity_logs', 'activity_logs_module_index')) {
                $table->index('module');
            }
            if (!$this->indexExists('activity_logs', 'activity_logs_user_type_user_id_index')) {
                $table->index(['user_type', 'user_id']);
            }
            if (!$this->indexExists('activity_logs', 'activity_logs_created_at_index')) {
                $table->index('created_at');
            }
        });

        // Login histories table
        Schema::table('login_histories', function (Blueprint $table) {
            if (!$this->indexExists('login_histories', 'login_histories_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('login_histories', 'login_histories_user_type_user_id_index')) {
                $table->index(['user_type', 'user_id']);
            }
            if (!$this->indexExists('login_histories', 'login_histories_created_at_index')) {
                $table->index('created_at');
            }
        });
    }

    public function down(): void
    {
        // Indexes will be dropped automatically if tables are dropped
    }

    protected function indexExists(string $table, string $index): bool
    {
        $connection = DB::connection()->getDriverName();
        
        if ($connection === 'sqlite') {
            // SQLite: check via pragma
            $indexes = DB::select("PRAGMA index_list({$table})");
            foreach ($indexes as $idx) {
                if ($idx->name === $index) {
                    return true;
                }
            }
            return false;
        }
        
        // MySQL/MariaDB
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
        return !empty($indexes);
    }
};
