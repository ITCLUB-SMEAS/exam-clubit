<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->boolean('block_multiple_monitors')->default(true)->after('block_right_click');
            $table->boolean('block_virtual_machine')->default(true)->after('block_multiple_monitors');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['block_multiple_monitors', 'block_virtual_machine']);
        });
    }
};
