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
        Schema::table('answers', function (Blueprint $table) {
            $table->text('answer_text')->nullable()->after('answer_order');
            $table->json('answer_options')->nullable()->after('answer_text');
            $table->decimal('points_awarded', 8, 2)->default(0)->after('is_correct');
            $table->boolean('needs_manual_review')->default(false)->after('points_awarded');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->dropColumn(['answer_text', 'answer_options', 'points_awarded', 'needs_manual_review']);
        });
    }
};
