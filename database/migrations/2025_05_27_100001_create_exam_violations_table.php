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
        Schema::create('exam_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreignId('exam_id')->references('id')->on('exams')->cascadeOnDelete();
            $table->foreignId('exam_session_id')->references('id')->on('exam_sessions')->cascadeOnDelete();
            $table->foreignId('grade_id')->references('id')->on('grades')->cascadeOnDelete();
            $table->string('violation_type', 50); // tab_switch, fullscreen_exit, copy_paste, right_click, devtools, blur, etc.
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional data like timestamp, count, etc.
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Indexes for faster queries
            $table->index(['student_id', 'exam_id', 'exam_session_id']);
            $table->index('violation_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_violations');
    }
};
