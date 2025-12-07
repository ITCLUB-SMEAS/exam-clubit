<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\Lesson;
use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamFactory extends Factory
{
    protected $model = Exam::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'lesson_id' => Lesson::factory(),
            'classroom_id' => Classroom::factory(),
            'duration' => 60,
            'description' => fake()->paragraph(),
            'random_question' => 'Y',
            'random_answer' => 'Y',
            'show_answer' => 'N',
            'passing_grade' => 70,
            'max_attempts' => 1,
            'question_limit' => null,
            'time_per_question' => null,
            'enable_partial_credit' => false,
            'enable_negative_marking' => false,
            'negative_marking_percentage' => 25.00,
        ];
    }
}
