<?php

namespace App\Jobs;

use App\Models\Student;
use App\Models\Exam;
use App\Models\Grade;
use App\Services\TelegramService;
use App\Services\ActivityLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessViolation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $studentId,
        public int $examId,
        public string $violationType,
        public int $violationCount,
        public string $ipAddress
    ) {}

    public function handle(TelegramService $telegram): void
    {
        $student = Student::find($this->studentId);
        $exam = Exam::find($this->examId);

        if (!$student || !$exam) return;

        // Send Telegram notification
        $telegram->sendViolationAlert([
            'student_name' => $student->name,
            'student_nisn' => $student->nisn,
            'exam_title' => $exam->title,
            'violation_type' => $this->violationType,
            'description' => "Pelanggaran terdeteksi",
            'violation_count' => $this->violationCount,
            'ip_address' => $this->ipAddress,
        ]);

        // Auto-block if 3+ violations
        if ($this->violationCount >= 3 && !$student->is_blocked) {
            $reason = "Auto-blocked: {$this->violationCount} pelanggaran";
            $student->block($reason);
            $telegram->sendStudentBlockedAlert($student, $reason);
        }
    }
}
