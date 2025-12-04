<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add token and QR settings to exam_sessions
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->string('access_token', 10)->nullable()->after('end_time');
            $table->string('qr_secret', 32)->nullable()->after('access_token');
            $table->boolean('require_attendance')->default(false)->after('qr_secret');
        });

        // Add attendance status to exam_groups (student enrollment)
        Schema::table('exam_groups', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable()->after('student_id');
            $table->string('checkin_method')->nullable()->after('checked_in_at'); // 'qr' or 'token'
            $table->string('checkin_ip', 45)->nullable()->after('checkin_method');
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn(['access_token', 'qr_secret', 'require_attendance']);
        });

        Schema::table('exam_groups', function (Blueprint $table) {
            $table->dropColumn(['checked_in_at', 'checkin_method', 'checkin_ip']);
        });
    }
};
