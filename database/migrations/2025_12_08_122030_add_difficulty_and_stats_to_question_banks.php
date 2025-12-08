<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium')->after('question_type');
            $table->unsignedInteger('usage_count')->default(0)->after('tags');
            $table->decimal('success_rate', 5, 2)->nullable()->after('usage_count');
            $table->timestamp('last_used_at')->nullable()->after('success_rate');
            
            $table->index('difficulty');
            $table->index('usage_count');
        });
    }

    public function down(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropIndex(['difficulty']);
            $table->dropIndex(['usage_count']);
            $table->dropColumn(['difficulty', 'usage_count', 'success_rate', 'last_used_at']);
        });
    }
};
