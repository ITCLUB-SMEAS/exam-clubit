<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\Question;
use App\Services\ExamScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdvancedScoringTest extends TestCase
{
    use RefreshDatabase;

    protected ExamScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExamScoringService();
    }

    /** @test */
    public function it_applies_negative_marking_for_wrong_single_choice()
    {
        $exam = Exam::factory()->create([
            'enable_negative_marking' => true,
            'negative_marking_percentage' => 25.00,
        ]);

        $question = Question::factory()->create([
            'exam_id' => $exam->id,
            'question_type' => Question::TYPE_MULTIPLE_CHOICE_SINGLE,
            'points' => 4,
            'answer' => 1,
        ]);

        [$isCorrect, $points, $needsReview] = $this->service->scoreAnswer(
            $question, 2, null, null, null, $exam
        );

        $this->assertEquals('N', $isCorrect);
        $this->assertEquals(-1, $points);
        $this->assertFalse($needsReview);
    }

    /** @test */
    public function it_does_not_apply_negative_marking_when_disabled()
    {
        $exam = Exam::factory()->create([
            'enable_negative_marking' => false,
        ]);

        $question = Question::factory()->create([
            'exam_id' => $exam->id,
            'question_type' => Question::TYPE_MULTIPLE_CHOICE_SINGLE,
            'points' => 4,
            'answer' => 1,
        ]);

        [$isCorrect, $points, $needsReview] = $this->service->scoreAnswer(
            $question, 2, null, null, null, $exam
        );

        $this->assertEquals('N', $isCorrect);
        $this->assertEquals(0, $points);
    }

    /** @test */
    public function it_applies_partial_credit_for_multiple_choice_multiple()
    {
        $exam = Exam::factory()->create([
            'enable_partial_credit' => true,
        ]);

        $question = Question::factory()->create([
            'exam_id' => $exam->id,
            'question_type' => Question::TYPE_MULTIPLE_CHOICE_MULTIPLE,
            'points' => 10,
            'correct_answers' => ['1', '2', '3'],
        ]);

        [$isCorrect, $points, $needsReview] = $this->service->scoreAnswer(
            $question, null, null, ['1', '2'], null, $exam
        );

        $this->assertEquals('N', $isCorrect);
        $this->assertEquals(6.67, $points);
    }

    /** @test */
    public function it_combines_partial_credit_and_negative_marking()
    {
        $exam = Exam::factory()->create([
            'enable_partial_credit' => true,
            'enable_negative_marking' => true,
            'negative_marking_percentage' => 25.00,
        ]);

        $question = Question::factory()->create([
            'exam_id' => $exam->id,
            'question_type' => Question::TYPE_MULTIPLE_CHOICE_MULTIPLE,
            'points' => 12,
            'correct_answers' => ['1', '2', '3'],
        ]);

        [$isCorrect, $points, $needsReview] = $this->service->scoreAnswer(
            $question, null, null, ['1', '2', '4'], null, $exam
        );

        $this->assertEquals('N', $isCorrect);
        $this->assertEquals(7, $points);
    }

    /** @test */
    public function it_gives_full_points_for_fully_correct_multiple_choice_multiple()
    {
        $exam = Exam::factory()->create([
            'enable_partial_credit' => true,
        ]);

        $question = Question::factory()->create([
            'exam_id' => $exam->id,
            'question_type' => Question::TYPE_MULTIPLE_CHOICE_MULTIPLE,
            'points' => 10,
            'correct_answers' => ['1', '2', '3'],
        ]);

        [$isCorrect, $points, $needsReview] = $this->service->scoreAnswer(
            $question, null, null, ['1', '2', '3'], null, $exam
        );

        $this->assertEquals('Y', $isCorrect);
        $this->assertEquals(10, $points);
    }

    /** @test */
    public function it_does_not_give_partial_credit_when_disabled()
    {
        $exam = Exam::factory()->create([
            'enable_partial_credit' => false,
        ]);

        $question = Question::factory()->create([
            'exam_id' => $exam->id,
            'question_type' => Question::TYPE_MULTIPLE_CHOICE_MULTIPLE,
            'points' => 10,
            'correct_answers' => ['1', '2', '3'],
        ]);

        [$isCorrect, $points, $needsReview] = $this->service->scoreAnswer(
            $question, null, null, ['1', '2'], null, $exam
        );

        $this->assertEquals('N', $isCorrect);
        $this->assertEquals(0, $points);
    }

    /** @test */
    public function it_stores_difficulty_level_on_question()
    {
        $question = Question::factory()->create([
            'difficulty' => 'hard',
        ]);

        $this->assertEquals('hard', $question->difficulty);
    }
}
