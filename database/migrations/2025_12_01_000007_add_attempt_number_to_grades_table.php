<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->integer('attempt_number')->default(1)->after('grade');
            $table->enum('status', ['passed', 'failed', 'pending'])->default('pending')->after('attempt_number');
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn(['attempt_number', 'status']);
        });
    }
};
