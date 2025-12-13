<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_groups', function (Blueprint $table) {
            $table->unique(['exam_id', 'exam_session_id', 'student_id'], 'exam_groups_unique');
        });
    }

    public function down(): void
    {
        Schema::table('exam_groups', function (Blueprint $table) {
            $table->dropUnique('exam_groups_unique');
        });
    }
};
