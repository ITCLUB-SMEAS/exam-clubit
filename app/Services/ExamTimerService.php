<?php

namespace App\Services;

use App\Models\ExamGroup;
use App\Models\Grade;
use Carbon\Carbon;

class ExamTimerService
{
    /**
     * Calculate remaining duration in milliseconds
     */
    public function calculateRemainingDurationMs(ExamGroup $examGroup, Grade $grade): int
    {
        $examDurationMs = $examGroup->exam->duration * 60000;
        $extensionMs = ($grade->time_extension ?? 0) * 60000;
        $totalDurationMs = $examDurationMs + $extensionMs;

        $startTime = $grade->start_time ?? Carbon::now();
        $elapsedMs = $startTime->diffInMilliseconds(Carbon::now());
        $remainingByDuration = max(0, $totalDurationMs - $elapsedMs);

        $sessionEnd = $examGroup->exam_session->end_time;
        $sessionRemainingMs = Carbon::now()->lt($sessionEnd)
            ? Carbon::now()->diffInMilliseconds($sessionEnd)
            : 0;

        return (int) min($remainingByDuration, $sessionRemainingMs);
    }

    /**
     * Check if exam time has expired
     */
    public function isTimeExpired(ExamGroup $examGroup, Grade $grade): bool
    {
        return $this->calculateRemainingDurationMs($examGroup, $grade) <= 0;
    }

    /**
     * Check if exam session is within valid time window
     *
     * @return string|null Error message if invalid, null if valid
     */
    public function validateSessionWindow(ExamGroup $examGroup): ?string
    {
        $now = Carbon::now();
        $session = $examGroup->exam_session;

        if ($now->lt($session->start_time)) {
            return 'Ujian belum dapat dimulai. Silakan cek jadwal.';
        }

        if ($now->gte($session->end_time)) {
            return 'Sesi ujian telah berakhir.';
        }

        return null;
    }

    /**
     * Initialize or update grade timing fields
     */
    public function initializeGradeTiming(Grade $grade): bool
    {
        $updated = false;

        if ($grade->start_time === null) {
            $grade->start_time = Carbon::now();
            $updated = true;
        }

        if ($grade->attempt_status === null || $grade->attempt_status === 'not_started') {
            $grade->attempt_status = 'in_progress';
            $grade->attempt_count = ($grade->attempt_count ?? 0) + 1;
            $updated = true;
        }

        return $updated;
    }
}
