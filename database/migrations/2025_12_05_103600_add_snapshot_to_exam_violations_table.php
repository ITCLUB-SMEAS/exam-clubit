<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_violations', function (Blueprint $table) {
            $table->string('snapshot_path')->nullable()->after('metadata');
        });
    }

    public function down(): void
    {
        Schema::table('exam_violations', function (Blueprint $table) {
            $table->dropColumn('snapshot_path');
        });
    }
};
