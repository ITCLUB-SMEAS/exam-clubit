<?php

namespace Tests\Unit;

use App\Models\Question;
use App\Services\ExamScoringService;
use PHPUnit\Framework\TestCase;

class ExamScoringServiceTest extends TestCase
{
    protected ExamScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExamScoringService();
    }

    /** @test */
    public function it_scores_multiple_choice_single_correctly()
    {
        $question = new Question([
            'question_type' => Question::TYPE_MULTIPLE_CHOICE_SINGLE,
            'answer' => 2,
            'points' => 10,
        ]);

        // Correct answer
        [$isCorrect, $points, $needsReview] = $this->service->scoreAnswer($question, 2, null, null);
        $this->assertEquals('Y', $isCorrect);
        $this->assertEquals(10, $points);
        $this->assertFalse($needsReview);

        // Wrong answer
        [$isCorrect, $points, $needsReview] = $this->service->scoreAnswer($question, 1, null, null);
        $this->assertEquals('N', $isCorrect);
        $this->assertEquals(0, $points);
    }

    /** @test */
    public function it_scores_true_false_correctly()
    {
        $question = new Question([
            'question_type' => Question::TYPE_TRUE_FALSE,
            'answer' => 1, // True
            'points' => 5,
        ]);

        [$isCorrect, $points, $needsReview] = $this->service->scoreAnswer($question, 1, null, null);
        $this->assertEquals('Y', $isCorrect);
        $this->assertEquals(5, $points);
    }

    /** @test */
    public function it_scores_multiple_choice_multiple_correctly()
    {
        $question = new Question([
            'question_type' => Question::TYPE_MULTIPLE_CHOICE_MULTIPLE,
            'correct_answers' => ['1', '3'],
            'points' => 10,
        ]);

        // All correct
        [$isCorrect, $points, $needsReview] = $this->service->scoreAnswer($question, null, null, ['1', '3']);
        $this->assertEquals('Y', $isCorrect);
        $this->assertEquals(10, $points);

        // Wrong
        [$isCorrect, $points, $needsReview] = $this->service->scoreAnswer($question, null, null, ['1', '2']);
        $this->assertEquals('N', $isCorrect);
        $this->assertEquals(0, $points);
    }

    /** @test */
    public function it_scores_short_answer_correctly()
    {
        $question = new Question([
            'question_type' => Question::TYPE_SHORT_ANSWER,
            'correct_answers' => ['jakarta', 'dki jakarta'],
            'points' => 5,
        ]);

        // Correct (case insensitive)
        [$isCorrect, $points, $needsReview] = $this->service->scoreAnswer($question, null, 'Jakarta', null);
        $this->assertEquals('Y', $isCorrect);
        $this->assertEquals(5, $points);

        // Alternative correct
        [$isCorrect, $points, $needsReview] = $this->service->scoreAnswer($question, null, 'DKI Jakarta', null);
        $this->assertEquals('Y', $isCorrect);

        // Wrong
        [$isCorrect, $points, $needsReview] = $this->service->scoreAnswer($question, null, 'Bandung', null);
        $this->assertEquals('N', $isCorrect);
    }

    /** @test */
    public function it_marks_essay_for_manual_review()
    {
        $question = new Question([
            'question_type' => Question::TYPE_ESSAY,
            'points' => 20,
        ]);

        [$isCorrect, $points, $needsReview] = $this->service->scoreAnswer($question, null, 'Some essay text', null);
        $this->assertEquals('N', $isCorrect);
        $this->assertEquals(0, $points);
        $this->assertTrue($needsReview);
    }

    /** @test */
    public function it_scores_matching_with_partial_credit()
    {
        $question = new Question([
            'question_type' => Question::TYPE_MATCHING,
            'matching_pairs' => [
                ['left' => 'A', 'right' => '1'],
                ['left' => 'B', 'right' => '2'],
                ['left' => 'C', 'right' => '3'],
            ],
            'points' => 9,
        ]);

        // All correct
        $matchingAnswers = ['A' => '1', 'B' => '2', 'C' => '3'];
        [$isCorrect, $points, $needsReview] = $this->service->scoreAnswer($question, null, null, null, $matchingAnswers);
        $this->assertEquals('Y', $isCorrect);
        $this->assertEquals(9, $points);

        // Partial (2 out of 3)
        $matchingAnswers = ['A' => '1', 'B' => '2', 'C' => '1'];
        [$isCorrect, $points, $needsReview] = $this->service->scoreAnswer($question, null, null, null, $matchingAnswers);
        $this->assertEquals('N', $isCorrect);
        $this->assertEquals(6, $points); // 2/3 * 9 = 6
    }

    /** @test */
    public function it_uses_default_points_when_not_specified()
    {
        $question = new Question([
            'question_type' => Question::TYPE_MULTIPLE_CHOICE_SINGLE,
            'answer' => 1,
            // No points specified
        ]);

        [$isCorrect, $points, $needsReview] = $this->service->scoreAnswer($question, 1, null, null);
        $this->assertEquals('Y', $isCorrect);
        $this->assertEquals(1, $points); // Default 1 point
    }
}
