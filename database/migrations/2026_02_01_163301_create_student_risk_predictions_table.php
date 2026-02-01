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
        Schema::create('student_risk_predictions', function (Blueprint $table) {
            $table->id();

            // References
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('exam_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('lesson_id')->nullable()->constrained()->onDelete('set null');

            // Risk Scores
            $table->decimal('risk_score', 5, 2)->default(0); // 0-100
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('low');

            // Risk Factors (JSON breakdown)
            $table->json('risk_factors')->nullable();
            // Structure: {
            //   "academic": {"score": 75, "weight": 0.40, "factors": ["declining_trend", "below_class_avg"]},
            //   "behavioral": {"score": 30, "weight": 0.30, "factors": ["high_violations"]},
            //   "engagement": {"score": 50, "weight": 0.20, "factors": ["irregular_timing"]},
            //   "contextual": {"score": 40, "weight": 0.10, "factors": ["difficult_exam"]}
            // }

            // Predictions
            $table->decimal('predicted_score', 5, 2)->nullable();
            $table->json('weak_topics')->nullable(); // Array of topic IDs/names
            $table->json('recommended_actions')->nullable();
            // Structure: ["review_topic_algebra", "assign_remedial", "contact_parent"]

            // Historical data snapshot (for analysis)
            $table->decimal('historical_average', 5, 2)->nullable();
            $table->integer('total_exams_taken')->default(0);
            $table->integer('total_passed')->default(0);
            $table->integer('total_failed')->default(0);
            $table->integer('total_violations')->default(0);

            // Intervention Tracking
            $table->boolean('teacher_notified')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->foreignId('intervention_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('intervention_notes')->nullable();
            $table->timestamp('intervened_at')->nullable();
            $table->enum('intervention_status', ['pending', 'notified', 'acknowledged', 'intervened', 'resolved'])->default('pending');

            // Validation (post-exam comparison)
            $table->decimal('actual_score', 5, 2)->nullable();
            $table->boolean('prediction_accurate')->nullable();
            $table->decimal('prediction_error', 5, 2)->nullable(); // Absolute difference

            // Meta
            $table->string('calculation_version')->default('1.0'); // For tracking algorithm changes
            $table->timestamp('expires_at')->nullable(); // When prediction becomes stale

            $table->timestamps();

            // Indexes for common queries
            $table->index(['student_id', 'exam_id']);
            $table->index(['risk_level', 'intervention_status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_risk_predictions');
    }
};
