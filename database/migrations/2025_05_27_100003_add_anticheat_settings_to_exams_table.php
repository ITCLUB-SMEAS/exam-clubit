<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table("exams", function (Blueprint $table) {
            // Anti-cheat settings for each exam
            $table
                ->boolean("anticheat_enabled")
                ->default(true)
                ->after("random_answer");
            $table
                ->boolean("fullscreen_required")
                ->default(true)
                ->after("anticheat_enabled");
            $table
                ->boolean("block_tab_switch")
                ->default(true)
                ->after("fullscreen_required");
            $table
                ->boolean("block_copy_paste")
                ->default(true)
                ->after("block_tab_switch");
            $table
                ->boolean("block_right_click")
                ->default(true)
                ->after("block_copy_paste");
            $table
                ->boolean("detect_devtools")
                ->default(true)
                ->after("block_right_click");

            // Violation thresholds
            $table
                ->integer("max_violations")
                ->default(10)
                ->after("detect_devtools");
            $table
                ->boolean("auto_submit_on_max_violations")
                ->default(true)
                ->after("max_violations");

            // Warning settings
            $table
                ->integer("warning_threshold")
                ->default(3)
                ->after("auto_submit_on_max_violations");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("exams", function (Blueprint $table) {
            $table->dropColumn([
                "anticheat_enabled",
                "fullscreen_required",
                "block_tab_switch",
                "block_copy_paste",
                "block_right_click",
                "detect_devtools",
                "max_violations",
                "auto_submit_on_max_violations",
                "warning_threshold",
            ]);
        });
    }
};
