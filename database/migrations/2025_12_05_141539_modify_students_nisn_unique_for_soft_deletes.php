<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique('students_nisn_unique');
            $table->unique(['nisn', 'deleted_at'], 'students_nisn_deleted_at_unique');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique('students_nisn_deleted_at_unique');
            $table->unique('nisn', 'students_nisn_unique');
        });
    }
};
