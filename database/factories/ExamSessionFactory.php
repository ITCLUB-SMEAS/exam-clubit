<?php

namespace Database\Factories;

use App\Models\Exam;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'title' => 'Sesi ' . $this->faker->word(),
            'start_time' => now(),
            'end_time' => now()->addHours(2),
        ];
    }
}
