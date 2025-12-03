<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_groups', function (Blueprint $table) {
            $table->index(['student_id', 'exam_session_id'], 'exam_groups_student_session');
        });
    }

    public function down(): void
    {
        Schema::table('exam_groups', function (Blueprint $table) {
            $table->dropIndex('exam_groups_student_session');
        });
    }
};
