<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeResource extends JsonResource
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
            'grade' => round($this->grade, 2),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'total_correct' => $this->total_correct,
            'violation_count' => $this->violation_count,
            'is_flagged' => $this->is_flagged,
            'duration_seconds' => $this->getDurationInSeconds(),
            'duration_formatted' => $this->getFormattedDuration(),
            'start_time' => $this->start_time?->toISOString(),
            'end_time' => $this->end_time?->toISOString(),
            'student' => new StudentResource($this->whenLoaded('student')),
            'exam' => new ExamResource($this->whenLoaded('exam')),
            'exam_session' => new ExamSessionResource($this->whenLoaded('exam_session')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * Get human-readable status label.
     */
    private function getStatusLabel(): string
    {
        return match ($this->status) {
            'passed' => 'Lulus',
            'failed' => 'Tidak Lulus',
            'in_progress' => 'Sedang Mengerjakan',
            'not_started' => 'Belum Mulai',
            default => ucfirst($this->status ?? 'unknown'),
        };
    }

    /**
     * Get duration in seconds.
     */
    private function getDurationInSeconds(): ?int
    {
        if (! $this->start_time || ! $this->end_time) {
            return null;
        }

        return $this->end_time->diffInSeconds($this->start_time);
    }

    /**
     * Get formatted duration string.
     */
    private function getFormattedDuration(): ?string
    {
        $seconds = $this->getDurationInSeconds();

        if ($seconds === null) {
            return null;
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d jam %d menit %d detik', $hours, $minutes, $secs);
        }

        if ($minutes > 0) {
            return sprintf('%d menit %d detik', $minutes, $secs);
        }

        return sprintf('%d detik', $secs);
    }
}
