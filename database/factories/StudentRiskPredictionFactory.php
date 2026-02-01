<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\Student;
use App\Models\StudentRiskPrediction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentRiskPrediction>
 */
class StudentRiskPredictionFactory extends Factory
{
    protected $model = StudentRiskPrediction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $riskScore = fake()->randomFloat(2, 0, 100);

        return [
            'student_id' => Student::factory(),
            'exam_id' => Exam::factory(),
            'lesson_id' => null,
            'risk_score' => $riskScore,
            'risk_level' => StudentRiskPrediction::getRiskLevelFromScore($riskScore),
            'risk_factors' => [
                'academic' => [
                    'score' => fake()->randomFloat(2, 0, 100),
                    'weight' => 0.40,
                    'factors' => fake()->randomElements(['low_average', 'declining_trend', 'high_fail_rate'], rand(0, 2)),
                ],
                'behavioral' => [
                    'score' => fake()->randomFloat(2, 0, 100),
                    'weight' => 0.30,
                    'factors' => fake()->randomElements(['high_violations', 'previously_flagged'], rand(0, 1)),
                ],
                'engagement' => [
                    'score' => fake()->randomFloat(2, 0, 100),
                    'weight' => 0.20,
                    'factors' => fake()->randomElements(['rushing_exams', 'inconsistent_timing'], rand(0, 1)),
                ],
                'contextual' => [
                    'score' => fake()->randomFloat(2, 0, 100),
                    'weight' => 0.10,
                    'factors' => [],
                ],
            ],
            'predicted_score' => fake()->randomFloat(2, 40, 90),
            'weak_topics' => null,
            'recommended_actions' => [
                [
                    'action' => 'review_recent_topics',
                    'description' => 'Review materi terbaru',
                    'priority' => 'medium',
                ],
            ],
            'historical_average' => fake()->randomFloat(2, 50, 80),
            'total_exams_taken' => fake()->numberBetween(0, 20),
            'total_passed' => fake()->numberBetween(0, 15),
            'total_failed' => fake()->numberBetween(0, 5),
            'total_violations' => fake()->numberBetween(0, 10),
            'teacher_notified' => false,
            'notified_at' => null,
            'intervention_by' => null,
            'intervention_notes' => null,
            'intervened_at' => null,
            'intervention_status' => 'pending',
            'actual_score' => null,
            'prediction_accurate' => null,
            'prediction_error' => null,
            'calculation_version' => StudentRiskPrediction::CURRENT_VERSION,
            'expires_at' => now()->addDays(7),
        ];
    }

    /**
     * State: High risk student
     */
    public function highRisk(): static
    {
        return $this->state(fn (array $attributes) => [
            'risk_score' => fake()->randomFloat(2, 70, 84),
            'risk_level' => 'high',
        ]);
    }

    /**
     * State: Critical risk student
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'risk_score' => fake()->randomFloat(2, 85, 100),
            'risk_level' => 'critical',
        ]);
    }

    /**
     * State: Low risk student
     */
    public function lowRisk(): static
    {
        return $this->state(fn (array $attributes) => [
            'risk_score' => fake()->randomFloat(2, 0, 30),
            'risk_level' => 'low',
        ]);
    }

    /**
     * State: Already notified
     */
    public function notified(): static
    {
        return $this->state(fn (array $attributes) => [
            'teacher_notified' => true,
            'notified_at' => now()->subHours(2),
            'intervention_status' => 'notified',
        ]);
    }

    /**
     * State: Intervention completed
     */
    public function intervened(): static
    {
        return $this->state(fn (array $attributes) => [
            'teacher_notified' => true,
            'notified_at' => now()->subDays(1),
            'intervention_by' => 1,
            'intervention_notes' => 'Contacted student and provided extra materials.',
            'intervened_at' => now()->subHours(6),
            'intervention_status' => 'intervened',
        ]);
    }

    /**
     * State: Validated (post-exam)
     */
    public function validated(): static
    {
        $predicted = fake()->randomFloat(2, 40, 80);
        $actual = fake()->randomFloat(2, 40, 80);
        $error = abs($predicted - $actual);

        return $this->state(fn (array $attributes) => [
            'predicted_score' => $predicted,
            'actual_score' => $actual,
            'prediction_error' => $error,
            'prediction_accurate' => $error <= 15,
        ]);
    }

    /**
     * State: Expired prediction
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDays(1),
        ]);
    }
}
