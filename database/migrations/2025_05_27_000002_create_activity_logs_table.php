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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // User yang melakukan aksi (polymorphic untuk admin & student)
            $table->string('user_type')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();

            // Informasi aksi
            $table->string('action');
            $table->string('module');
            $table->text('description');

            // Data terkait (polymorphic)
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            // Data perubahan (untuk audit)
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Informasi request
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable();

            // Metadata tambahan
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes untuk performa query
            $table->index(['user_type', 'user_id']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('action');
            $table->index('module');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
