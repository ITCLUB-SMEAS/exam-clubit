<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->boolean('enable_partial_credit')->default(false)->after('time_per_question');
            $table->boolean('enable_negative_marking')->default(false)->after('enable_partial_credit');
            $table->decimal('negative_marking_percentage', 5, 2)->default(25.00)->after('enable_negative_marking');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['enable_partial_credit', 'enable_negative_marking', 'negative_marking_percentage']);
        });
    }
};
