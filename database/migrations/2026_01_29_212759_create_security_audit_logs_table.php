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
        Schema::create('security_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('event_type', 100)->index();
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info')->index();
            $table->string('user_id', 50)->nullable()->index();
            $table->string('user_type', 50)->nullable(); // 'student', 'admin', 'guru', etc.
            $table->string('ip_address', 45)->nullable()->index(); // IPv6 compatible
            $table->string('user_agent', 500)->nullable();
            $table->string('url', 2000)->nullable();
            $table->string('method', 10)->nullable();
            $table->json('context')->nullable();
            $table->text('description')->nullable();
            $table->string('session_id', 64)->nullable()->index();
            $table->timestamp('created_at')->useCurrent()->index();

            // Composite indexes for common queries
            $table->index(['event_type', 'created_at']);
            $table->index(['severity', 'created_at']);
            $table->index(['user_id', 'event_type']);
            $table->index(['ip_address', 'event_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_audit_logs');
    }
};
