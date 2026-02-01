<?php

namespace Tests\Unit\Services;

use App\Models\Exam;
use App\Models\Grade;
use App\Models\Student;
use App\Models\StudentRiskPrediction;
use App\Services\PredictiveAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictiveAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PredictiveAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PredictiveAnalyticsService;
    }

    /** @test */
    public function it_can_calculate_risk_score_for_student_without_history()
    {
        $student = Student::factory()->create();

        $result = $this->service->calculateRiskScore($student);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('risk_score', $result);
        $this->assertArrayHasKey('risk_level', $result);
        $this->assertArrayHasKey('risk_factors', $result);
        $this->assertArrayHasKey('predicted_score', $result);
        $this->assertArrayHasKey('recommended_actions', $result);

        // Student with no history should have moderate risk (unknown)
        $this->assertGreaterThanOrEqual(0, $result['risk_score']);
        $this->assertLessThanOrEqual(100, $result['risk_score']);
    }

    /** @test */
    public function it_calculates_higher_risk_for_students_with_low_grades()
    {
        $student = Student::factory()->create();
        $exam = Exam::factory()->create(['classroom_id' => $student->classroom_id]);

        // Create grades with low scores
        for ($i = 0; $i < 5; $i++) {
            Grade::factory()->create([
                'student_id' => $student->id,
                'exam_id' => $exam->id,
                'grade' => rand(30, 50), // Low grades
                'status' => 'failed',
                'end_time' => now()->subDays($i),
            ]);
        }

        $result = $this->service->calculateRiskScore($student);

        // Should have higher risk due to low grades
        $this->assertGreaterThan(50, $result['risk_score']);
        $this->assertContains($result['risk_level'], ['high', 'critical', 'medium']);
    }

    /** @test */
    public function it_calculates_lower_risk_for_students_with_high_grades()
    {
        $student = Student::factory()->create();
        $exam = Exam::factory()->create(['classroom_id' => $student->classroom_id]);

        // Create grades with high scores
        for ($i = 0; $i < 5; $i++) {
            Grade::factory()->create([
                'student_id' => $student->id,
                'exam_id' => $exam->id,
                'grade' => rand(85, 100), // High grades
                'status' => 'passed',
                'violation_count' => 0,
                'end_time' => now()->subDays($i),
            ]);
        }

        $result = $this->service->calculateRiskScore($student);

        // Should have lower risk
        $this->assertLessThan(50, $result['risk_score']);
    }

    /** @test */
    public function it_includes_behavioral_factors_for_students_with_violations()
    {
        $student = Student::factory()->create();
        $exam = Exam::factory()->create(['classroom_id' => $student->classroom_id]);

        // Create grades with violations
        for ($i = 0; $i < 3; $i++) {
            Grade::factory()->create([
                'student_id' => $student->id,
                'exam_id' => $exam->id,
                'grade' => 70,
                'status' => 'passed',
                'violation_count' => 5, // High violations
                'is_flagged' => true,
                'end_time' => now()->subDays($i),
            ]);
        }

        $result = $this->service->calculateRiskScore($student);

        // Check behavioral factors are included
        $this->assertArrayHasKey('behavioral', $result['risk_factors']);
        $this->assertGreaterThan(0, $result['risk_factors']['behavioral']['score']);
    }

    /** @test */
    public function it_returns_correct_risk_level_based_on_score()
    {
        // Test low risk
        $this->assertEquals('low', StudentRiskPrediction::getRiskLevelFromScore(20));

        // Test medium risk
        $this->assertEquals('medium', StudentRiskPrediction::getRiskLevelFromScore(55));

        // Test high risk
        $this->assertEquals('high', StudentRiskPrediction::getRiskLevelFromScore(75));

        // Test critical risk
        $this->assertEquals('critical', StudentRiskPrediction::getRiskLevelFromScore(90));
    }

    /** @test */
    public function it_generates_recommendations_for_high_risk_students()
    {
        $student = Student::factory()->create();
        $exam = Exam::factory()->create(['classroom_id' => $student->classroom_id]);

        // Create failing grades to generate high risk
        for ($i = 0; $i < 5; $i++) {
            Grade::factory()->create([
                'student_id' => $student->id,
                'exam_id' => $exam->id,
                'grade' => rand(20, 40),
                'status' => 'failed',
                'end_time' => now()->subDays($i),
            ]);
        }

        $result = $this->service->calculateRiskScore($student);

        // Should have recommendations
        $this->assertIsArray($result['recommended_actions']);
        $this->assertNotEmpty($result['recommended_actions']);
    }

    /** @test */
    public function it_can_generate_predictions_for_exam()
    {
        $exam = Exam::factory()->create();

        // Create students in the same classroom
        $students = Student::factory()->count(3)->create([
            'classroom_id' => $exam->classroom_id,
        ]);

        $predictions = $this->service->generatePredictionsForExam($exam);

        $this->assertCount(3, $predictions);

        // Check predictions are stored in database
        $this->assertDatabaseCount('student_risk_predictions', 3);

        foreach ($students as $student) {
            $this->assertDatabaseHas('student_risk_predictions', [
                'student_id' => $student->id,
                'exam_id' => $exam->id,
            ]);
        }
    }

    /** @test */
    public function prediction_model_can_mark_as_notified()
    {
        $prediction = StudentRiskPrediction::factory()->create([
            'teacher_notified' => false,
            'intervention_status' => 'pending',
        ]);

        $prediction->markAsNotified();

        $prediction->refresh();

        $this->assertTrue($prediction->teacher_notified);
        $this->assertEquals('notified', $prediction->intervention_status);
        $this->assertNotNull($prediction->notified_at);
    }

    /** @test */
    public function prediction_model_can_record_intervention()
    {
        $prediction = StudentRiskPrediction::factory()->create([
            'intervention_status' => 'acknowledged',
        ]);

        $prediction->recordIntervention(1, 'Called the student for counseling');

        $prediction->refresh();

        $this->assertEquals(1, $prediction->intervention_by);
        $this->assertEquals('Called the student for counseling', $prediction->intervention_notes);
        $this->assertEquals('intervened', $prediction->intervention_status);
        $this->assertNotNull($prediction->intervened_at);
    }

    /** @test */
    public function prediction_model_can_validate_prediction()
    {
        $prediction = StudentRiskPrediction::factory()->create([
            'predicted_score' => 70,
            'actual_score' => null,
        ]);

        $prediction->validatePrediction(75);

        $prediction->refresh();

        $this->assertEquals(75, $prediction->actual_score);
        $this->assertEquals(5, $prediction->prediction_error);
        $this->assertTrue($prediction->prediction_accurate); // Within 15 points
    }

    /** @test */
    public function prediction_is_marked_inaccurate_when_error_exceeds_threshold()
    {
        $prediction = StudentRiskPrediction::factory()->create([
            'predicted_score' => 70,
        ]);

        $prediction->validatePrediction(45); // 25 points difference

        $prediction->refresh();

        $this->assertEquals(25, $prediction->prediction_error);
        $this->assertFalse($prediction->prediction_accurate);
    }

    /** @test */
    public function high_risk_scope_returns_correct_predictions()
    {
        StudentRiskPrediction::factory()->create(['risk_level' => 'low']);
        StudentRiskPrediction::factory()->create(['risk_level' => 'medium']);
        $high = StudentRiskPrediction::factory()->create(['risk_level' => 'high']);
        $critical = StudentRiskPrediction::factory()->create(['risk_level' => 'critical']);

        $highRisk = StudentRiskPrediction::highRisk()->get();

        $this->assertCount(2, $highRisk);
        $this->assertTrue($highRisk->contains($high));
        $this->assertTrue($highRisk->contains($critical));
    }

    /** @test */
    public function pending_intervention_scope_works_correctly()
    {
        StudentRiskPrediction::factory()->create(['intervention_status' => 'pending']);
        StudentRiskPrediction::factory()->create(['intervention_status' => 'notified']);
        StudentRiskPrediction::factory()->create(['intervention_status' => 'resolved']);

        $pending = StudentRiskPrediction::pendingIntervention()->get();

        $this->assertCount(2, $pending);
    }
}
