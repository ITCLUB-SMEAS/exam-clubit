<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * Enable auto_submit_on_max_violations for all existing exams
     */
    public function up(): void
    {
        // Update all existing exams to enable auto submit when max violations reached
        DB::table("exams")->update([
            "auto_submit_on_max_violations" => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to false if needed
        DB::table("exams")->update([
            "auto_submit_on_max_violations" => false,
        ]);
    }
};
