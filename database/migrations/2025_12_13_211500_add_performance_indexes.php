<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Grades table
        $this->safeAddIndex('grades', 'end_time');
        $this->safeAddIndex('grades', 'start_time');

        // Exam sessions
        $this->safeAddIndex('exam_sessions', ['start_time', 'end_time'], 'exam_sessions_start_end_idx');

        // Students
        $this->safeAddIndex('students', 'classroom_id');
        $this->safeAddIndex('students', 'room_id');
        $this->safeAddIndex('students', 'is_blocked');

        // Answers
        $this->safeAddIndex('answers', 'needs_manual_review');

        // Question bank
        $this->safeAddIndex('question_banks', 'category_id');
        $this->safeAddIndex('question_banks', 'question_type');
        $this->safeAddIndex('question_banks', 'difficulty');
    }

    public function down(): void
    {
        $this->safeDropIndex('grades', ['end_time']);
        $this->safeDropIndex('grades', ['start_time']);
        $this->safeDropIndex('exam_sessions', 'exam_sessions_start_end_idx');
        $this->safeDropIndex('students', ['classroom_id']);
        $this->safeDropIndex('students', ['room_id']);
        $this->safeDropIndex('students', ['is_blocked']);
        $this->safeDropIndex('answers', ['needs_manual_review']);
        $this->safeDropIndex('question_banks', ['category_id']);
        $this->safeDropIndex('question_banks', ['question_type']);
        $this->safeDropIndex('question_banks', ['difficulty']);
    }

    private function safeAddIndex(string $table, $columns, ?string $name = null): void
    {
        try {
            Schema::table($table, function (Blueprint $t) use ($columns, $name) {
                if ($name) {
                    $t->index($columns, $name);
                } else {
                    $t->index($columns);
                }
            });
        } catch (\Exception $e) {
            // Index already exists
        }
    }

    private function safeDropIndex(string $table, $columns): void
    {
        try {
            Schema::table($table, function (Blueprint $t) use ($columns) {
                $t->dropIndex($columns);
            });
        } catch (\Exception $e) {
            // Index doesn't exist
        }
    }
};
