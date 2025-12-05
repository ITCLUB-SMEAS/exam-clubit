<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('version_number');
            $table->json('data'); // Stores full question snapshot
            $table->string('change_reason')->nullable();
            $table->timestamps();

            $table->index(['question_id', 'version_number']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->integer('current_version')->default(1)->after('id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_versions');
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('current_version');
        });
    }
};
