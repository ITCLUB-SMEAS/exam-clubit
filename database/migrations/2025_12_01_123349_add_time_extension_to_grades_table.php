<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->integer('time_extension')->default(0)->after('duration');
            $table->string('extension_reason')->nullable()->after('time_extension');
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn(['time_extension', 'extension_reason']);
        });
    }
};
