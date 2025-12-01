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
        Schema::table('grades', function (Blueprint $table) {
            $table->decimal('points_possible', 8, 2)->default(0)->after('grade');
            $table->decimal('points_earned', 8, 2)->default(0)->after('points_possible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn(['points_possible', 'points_earned']);
        });
    }
};
