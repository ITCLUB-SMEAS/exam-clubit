<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->decimal('passing_grade', 5, 2)->default(0)->after('show_answer'); // KKM, 0 = tidak ada
            $table->integer('max_attempts')->default(1)->after('passing_grade'); // Jumlah percobaan (untuk remedial)
            $table->integer('question_limit')->nullable()->after('max_attempts'); // Batasi jumlah soal, null = semua
            $table->integer('time_per_question')->nullable()->after('question_limit'); // Detik per soal, null = tidak ada
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['passing_grade', 'max_attempts', 'question_limit', 'time_per_question']);
        });
    }
};
