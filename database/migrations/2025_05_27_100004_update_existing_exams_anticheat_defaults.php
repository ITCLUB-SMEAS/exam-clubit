<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * Update existing exams to have anti-cheat enabled by default
     */
    public function up(): void
    {
        // Update all existing exams that might have NULL values
        DB::table("exams")
            ->whereNull("anticheat_enabled")
            ->update([
                "anticheat_enabled" => true,
                "fullscreen_required" => true,
                "block_tab_switch" => true,
                "block_copy_paste" => true,
                "block_right_click" => true,
                "detect_devtools" => true,
                "max_violations" => 10,
                "warning_threshold" => 3,
                "auto_submit_on_max_violations" => true,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse - the columns will be dropped if parent migration is rolled back
    }
};
