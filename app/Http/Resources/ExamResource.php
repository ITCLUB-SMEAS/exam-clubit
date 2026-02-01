<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'duration' => $this->duration,
            'passing_grade' => $this->passing_grade,
            'max_attempts' => $this->max_attempts,
            'question_limit' => $this->question_limit,
            'time_per_question' => $this->time_per_question,
            'random_question' => $this->random_question === 'Y',
            'random_answer' => $this->random_answer === 'Y',
            'show_answer' => $this->show_answer === 'Y',
            'adaptive_mode' => $this->adaptive_mode,
            'questions_count' => $this->whenCounted('questions'),
            'lesson' => new LessonResource($this->whenLoaded('lesson')),
            'classroom' => new ClassroomResource($this->whenLoaded('classroom')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
