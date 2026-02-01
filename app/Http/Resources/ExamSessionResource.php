<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamSessionResource extends JsonResource
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
            'start_time' => $this->start_time?->toISOString(),
            'end_time' => $this->end_time?->toISOString(),
            'is_active' => $this->isActive(),
            'exam' => new ExamResource($this->whenLoaded('exam')),
            'participants_count' => $this->whenCounted('exam_groups'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * Check if the session is currently active.
     */
    private function isActive(): bool
    {
        $now = now();

        return $this->start_time <= $now && $this->end_time >= $now;
    }
}
