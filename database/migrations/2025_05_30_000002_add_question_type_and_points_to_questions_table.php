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
        Schema::table('questions', function (Blueprint $table) {
            $table->string('question_type')->default('multiple_choice_single')->after('question');
            $table->decimal('points', 8, 2)->default(1)->after('question_type');
            $table->json('correct_answers')->nullable()->after('answer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['question_type', 'points', 'correct_answers']);
        });
    }
};
