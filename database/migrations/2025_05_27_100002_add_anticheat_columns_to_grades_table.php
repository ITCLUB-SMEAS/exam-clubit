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
            // Anti-cheat violation counters
            $table->integer('violation_count')->default(0)->after('grade');
            $table->integer('tab_switch_count')->default(0)->after('violation_count');
            $table->integer('fullscreen_exit_count')->default(0)->after('tab_switch_count');
            $table->integer('copy_paste_count')->default(0)->after('fullscreen_exit_count');
            $table->integer('right_click_count')->default(0)->after('copy_paste_count');
            $table->integer('blur_count')->default(0)->after('right_click_count');

            // Anti-cheat status
            $table->boolean('is_flagged')->default(false)->after('blur_count');
            $table->text('flag_reason')->nullable()->after('is_flagged');

            // Anti-cheat metadata
            $table->json('anticheat_metadata')->nullable()->after('flag_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn([
                'violation_count',
                'tab_switch_count',
                'fullscreen_exit_count',
                'copy_paste_count',
                'right_click_count',
                'blur_count',
                'is_flagged',
                'flag_reason',
                'anticheat_metadata',
            ]);
        });
    }
};
