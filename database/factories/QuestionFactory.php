<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Exam;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'question' => fake()->sentence() . '?',
            'question_type' => Question::TYPE_MULTIPLE_CHOICE_SINGLE,
            'difficulty' => 'medium',
            'points' => 1,
            'option_1' => 'Option A',
            'option_2' => 'Option B',
            'option_3' => 'Option C',
            'option_4' => 'Option D',
            'option_5' => null,
            'answer' => 1,
            'correct_answers' => null,
            'matching_pairs' => null,
        ];
    }
}
