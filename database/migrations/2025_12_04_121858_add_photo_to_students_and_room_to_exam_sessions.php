<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('gender');
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->string('room')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('photo');
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn('room');
        });
    }
};
