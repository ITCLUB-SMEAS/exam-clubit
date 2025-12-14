<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class GradeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'exam_id' => Exam::factory(),
            'exam_session_id' => ExamSession::factory(),
            'duration' => 60,
            'total_correct' => 0,
            'grade' => $this->faker->numberBetween(0, 100),
            'status' => 'pending',
        ];
    }
}
