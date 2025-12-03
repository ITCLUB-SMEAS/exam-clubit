<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
