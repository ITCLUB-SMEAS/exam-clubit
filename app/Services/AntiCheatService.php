<?php

namespace App\Services;

use App\Events\StudentBlocked;
use App\Events\ViolationRecorded;
use App\Models\Exam;
use App\Models\ExamViolation;
use App\Models\Grade;
use App\Models\Student;
use App\Models\User;
use App\Notifications\ExamViolationNotification;
use Illuminate\Support\Facades\Log;

class AntiCheatService
{
    /**
     * Record a violation
     */
    public static function recordViolation(
        Student $student,
        Exam $exam,
        int $examSessionId,
        Grade $grade,
        string $violationType,
        ?string $description = null,
        ?array $metadata = null,
        ?string $snapshotPath = null
    ): ExamViolation {
        // Create the violation record
        $violation = ExamViolation::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'exam_session_id' => $examSessionId,
            'grade_id' => $grade->id,
            'violation_type' => $violationType,
            'description' => $description ?? self::getDefaultDescription($violationType),
            'metadata' => $metadata,
            'snapshot_path' => $snapshotPath,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Increment violation counter on grade
        $grade->incrementViolation($violationType);

        // Log the violation for activity tracking
        ActivityLogService::log(
            action: 'violation',
            module: 'anticheat',
            description: 'Pelanggaran: '.self::getViolationLabel($violationType),
            subject: $violation,
            metadata: [
                'violation_type' => $violationType,
                'exam_id' => $exam->id,
                'exam_title' => $exam->title,
                'total_violations' => $grade->violation_count,
            ]
        );

        // Check if auto-flag should be applied
        self::checkAndFlagIfNeeded($grade, $exam);

        // Notify admins (only for serious violations or when threshold reached)
        if ($grade->violation_count >= 2) {
            self::notifyAdmins($student, $exam, $violationType, $grade->violation_count, $snapshotPath);
        }

        // Log for monitoring
        Log::channel('daily')->info('Anti-Cheat Violation', [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'exam_id' => $exam->id,
            'exam_title' => $exam->title,
            'violation_type' => $violationType,
            'total_violations' => $grade->violation_count,
        ]);

        // Dispatch violation recorded event
        ViolationRecorded::dispatch($violation, $grade->violation_count);

        return $violation;
    }

    /**
     * Notify all admins about violation
     */
    protected static function notifyAdmins(Student $student, Exam $exam, string $violationType, int $totalViolations, ?string $snapshotPath = null): void
    {
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new ExamViolationNotification(
                $student->name,
                $exam->title,
                self::getViolationLabel($violationType),
                $totalViolations
            ));
        }

        // Get full path for snapshot (local disk root is storage/app/private)
        $fullSnapshotPath = null;
        if ($snapshotPath && self::isValidSnapshotPath($snapshotPath)) {
            $fullSnapshotPath = storage_path('app/private/'.$snapshotPath);
        }

        // Send Telegram notification with photo
        app(TelegramService::class)->sendViolationAlert([
            'student_name' => $student->name,
            'student_nisn' => $student->nisn,
            'exam_title' => $exam->title,
            'violation_type' => self::getViolationLabel($violationType),
            'description' => self::getDefaultDescription($violationType),
            'violation_count' => $totalViolations,
            'ip_address' => request()->ip(),
        ], $fullSnapshotPath);
    }

    /**
     * Record multiple violations at once (batch)
     *
     * @param  array  $violations  Array of ['type' => string, 'description' => string|null, 'metadata' => array|null]
     */
    public static function recordBatchViolations(
        Student $student,
        Exam $exam,
        int $examSessionId,
        Grade $grade,
        array $violations
    ): array {
        $results = [];

        foreach ($violations as $violation) {
            $results[] = self::recordViolation(
                $student,
                $exam,
                $examSessionId,
                $grade,
                $violation['type'],
                $violation['description'] ?? null,
                $violation['metadata'] ?? null
            );
        }

        return $results;
    }

    /**
     * Get violations for a specific exam attempt
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getViolationsForGrade(int $gradeId)
    {
        return ExamViolation::byGrade($gradeId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get violation summary for a grade
     */
    public static function getViolationSummary(Grade $grade): array
    {
        $violations = ExamViolation::byGrade($grade->id)
            ->selectRaw('violation_type, COUNT(*) as count')
            ->groupBy('violation_type')
            ->pluck('count', 'violation_type')
            ->toArray();

        return [
            'total' => $grade->violation_count,
            'by_type' => $violations,
            'is_flagged' => $grade->is_flagged,
            'flag_reason' => $grade->flag_reason,
            'details' => [
                'tab_switch' => $violations[ExamViolation::TYPE_TAB_SWITCH] ?? 0,
                'fullscreen_exit' => $violations[ExamViolation::TYPE_FULLSCREEN_EXIT] ?? 0,
                'copy_paste' => $violations[ExamViolation::TYPE_COPY_PASTE] ?? 0,
                'right_click' => $violations[ExamViolation::TYPE_RIGHT_CLICK] ?? 0,
                'blur' => $violations[ExamViolation::TYPE_BLUR] ?? 0,
                'devtools' => $violations[ExamViolation::TYPE_DEVTOOLS] ?? 0,
                'keyboard_shortcut' => $violations[ExamViolation::TYPE_KEYBOARD_SHORTCUT] ?? 0,
            ],
        ];
    }

    /**
     * Check if violation limit has been exceeded
     */
    public static function hasExceededLimit(Grade $grade, Exam $exam): bool
    {
        $maxViolations = $exam->max_violations ?? 3;

        return $grade->violation_count >= $maxViolations;
    }

    /**
     * Check if warning threshold has been reached
     */
    public static function hasReachedWarningThreshold(Grade $grade, Exam $exam): bool
    {
        $warningThreshold = $exam->warning_threshold ?? 3;

        return $grade->violation_count >= $warningThreshold;
    }

    /**
     * Get remaining violations before limit
     */
    public static function getRemainingViolations(Grade $grade, Exam $exam): int
    {
        $maxViolations = $exam->max_violations ?? 3;

        return max(0, $maxViolations - $grade->violation_count);
    }

    /**
     * Check and flag the grade if violation threshold is met
     */
    protected static function checkAndFlagIfNeeded(Grade $grade, Exam $exam): void
    {
        $warningThreshold = $exam->warning_threshold ?? 2;
        $maxViolations = $exam->max_violations ?? 3;

        // Flag if exceeded warning threshold but not yet flagged
        if ($grade->violation_count >= $warningThreshold && ! $grade->is_flagged) {
            $grade->flagAsSuspicious(
                "Melebihi batas peringatan ({$grade->violation_count} pelanggaran)"
            );
        }

        // Update flag reason if max violations exceeded
        if ($grade->violation_count >= $maxViolations) {
            $grade->update([
                'flag_reason' => "Melebihi batas maksimal pelanggaran ({$grade->violation_count}/{$maxViolations})",
            ]);
        }

        // Auto-block student at 3rd violation
        self::checkAndBlockStudent($grade);
    }

    /**
     * Check and block student if they exceed the configured violation threshold
     */
    public static function checkAndBlockStudent(Grade $grade): bool
    {
        // Check if auto-blocking is enabled
        if (! config('security.anticheat.auto_block_enabled', true)) {
            return false;
        }

        $threshold = (int) config('security.anticheat.auto_block_threshold', 3);

        // Threshold of 0 means disabled
        if ($threshold <= 0) {
            return false;
        }

        $student = Student::find($grade->student_id);

        if (! $student || $student->is_blocked) {
            return false;
        }

        if ($grade->violation_count >= $threshold) {
            $reason = "Akun diblokir otomatis: {$grade->violation_count} pelanggaran anti-cheat (batas: {$threshold})";
            $student->block($reason);

            ActivityLogService::log(
                action: 'block',
                module: 'anticheat',
                description: "Akun siswa diblokir otomatis karena {$grade->violation_count} pelanggaran",
                subject: $student,
                metadata: [
                    'grade_id' => $grade->id,
                    'violation_count' => $grade->violation_count,
                    'threshold' => $threshold,
                ]
            );

            Log::channel('daily')->warning('Student Auto-Blocked', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'violation_count' => $grade->violation_count,
                'threshold' => $threshold,
            ]);

            // Dispatch student blocked event
            StudentBlocked::dispatch($student, $reason);

            // Send Telegram notification
            app(TelegramService::class)->sendStudentBlockedAlert($student, $reason);

            return true;
        }

        return false;
    }

    /**
     * Get the anti-cheat configuration for an exam
     */
    public static function getAntiCheatConfig(Exam $exam): array
    {
        return [
            'enabled' => $exam->anticheat_enabled ?? true,
            'fullscreen_required' => false, // Disabled - causes issues on some devices
            'block_tab_switch' => $exam->block_tab_switch ?? true,
            'block_copy_paste' => $exam->block_copy_paste ?? true,
            'block_right_click' => $exam->block_right_click ?? true,
            'detect_devtools' => $exam->detect_devtools ?? true,
            'max_violations' => $exam->max_violations ?? 3,
            'warning_threshold' => $exam->warning_threshold ?? 2,
            'auto_submit_on_max_violations' => $exam->auto_submit_on_max_violations ?? true,
        ];
    }

    /**
     * Get default description for a violation type
     */
    public static function getDefaultDescription(string $type): string
    {
        $descriptions = [
            ExamViolation::TYPE_TAB_SWITCH => 'Siswa berpindah ke tab atau window lain',
            ExamViolation::TYPE_FULLSCREEN_EXIT => 'Siswa keluar dari mode fullscreen',
            ExamViolation::TYPE_COPY_PASTE => 'Siswa mencoba melakukan copy atau paste',
            ExamViolation::TYPE_RIGHT_CLICK => 'Siswa mencoba klik kanan',
            ExamViolation::TYPE_DEVTOOLS => 'Siswa mencoba membuka Developer Tools',
            ExamViolation::TYPE_BLUR => 'Window ujian kehilangan fokus',
            ExamViolation::TYPE_SCREENSHOT => 'Siswa mencoba mengambil screenshot',
            ExamViolation::TYPE_KEYBOARD_SHORTCUT => 'Siswa mencoba menggunakan shortcut keyboard terlarang',
            ExamViolation::TYPE_MULTIPLE_MONITORS => 'Terdeteksi penggunaan multiple monitor',
            ExamViolation::TYPE_REMOTE_DESKTOP => 'Terdeteksi penggunaan remote desktop',
            ExamViolation::TYPE_VIRTUAL_MACHINE => 'Terdeteksi penggunaan virtual machine',
            ExamViolation::TYPE_NO_FACE => 'Wajah siswa tidak terdeteksi di kamera',
            ExamViolation::TYPE_MULTIPLE_FACES => 'Terdeteksi lebih dari satu wajah di kamera',
            ExamViolation::TYPE_SUSPICIOUS_AUDIO => 'Terdeteksi suara mencurigakan (berbicara/berbisik)',
            ExamViolation::TYPE_TIME_MANIPULATION => 'Terdeteksi manipulasi waktu sistem',
            ExamViolation::TYPE_EXTENDED_BLUR => 'Window tidak fokus dalam waktu lama',
            ExamViolation::TYPE_PROLONGED_BLUR => 'Window tidak fokus berkepanjangan',
            ExamViolation::TYPE_EXCESSIVE_BLUR => 'Total waktu tidak fokus melebihi batas',
        ];

        return $descriptions[$type] ?? 'Pelanggaran tidak diketahui';
    }

    /**
     * Get violation type label
     */
    public static function getViolationLabel(string $type): string
    {
        $labels = ExamViolation::getViolationTypes();

        return $labels[$type] ?? $type;
    }

    /**
     * Get statistics for an exam session
     */
    public static function getExamSessionStats(int $examId, int $examSessionId): array
    {
        $violations = ExamViolation::where('exam_id', $examId)
            ->where('exam_session_id', $examSessionId)
            ->get();

        $byType = $violations->groupBy('violation_type')
            ->map(fn ($group) => $group->count())
            ->toArray();

        $byStudent = $violations->groupBy('student_id')
            ->map(fn ($group) => $group->count())
            ->toArray();

        $flaggedGrades = Grade::where('exam_id', $examId)
            ->where('exam_session_id', $examSessionId)
            ->where('is_flagged', true)
            ->count();

        return [
            'total_violations' => $violations->count(),
            'unique_students' => count($byStudent),
            'flagged_students' => $flaggedGrades,
            'by_type' => $byType,
            'most_common' => $violations->isNotEmpty()
                ? $violations->groupBy('violation_type')->sortByDesc(fn ($group) => $group->count())->keys()->first()
                : null,
        ];
    }

    /**
     * Clear all violations for a grade (admin function)
     */
    public static function clearViolations(Grade $grade, string $reason = ''): void
    {
        // Log the action
        ActivityLogService::log(
            action: 'clear_violations',
            module: 'anticheat',
            description: "Pelanggaran dihapus: {$reason}",
            subject: $grade,
            metadata: [
                'previous_violation_count' => $grade->violation_count,
                'reason' => $reason,
            ]
        );

        // Delete violation records
        ExamViolation::byGrade($grade->id)->delete();

        // Reset counters
        $grade->update([
            'violation_count' => 0,
            'tab_switch_count' => 0,
            'fullscreen_exit_count' => 0,
            'copy_paste_count' => 0,
            'right_click_count' => 0,
            'blur_count' => 0,
            'is_flagged' => false,
            'flag_reason' => null,
        ]);
    }

    /**
     * Validate if a violation type is valid
     */
    public static function isValidViolationType(string $type): bool
    {
        return in_array($type, [
            ExamViolation::TYPE_TAB_SWITCH,
            ExamViolation::TYPE_FULLSCREEN_EXIT,
            ExamViolation::TYPE_COPY_PASTE,
            ExamViolation::TYPE_RIGHT_CLICK,
            ExamViolation::TYPE_DEVTOOLS,
            ExamViolation::TYPE_BLUR,
            ExamViolation::TYPE_SCREENSHOT,
            ExamViolation::TYPE_KEYBOARD_SHORTCUT,
            ExamViolation::TYPE_MULTIPLE_MONITORS,
            ExamViolation::TYPE_REMOTE_DESKTOP,
            ExamViolation::TYPE_VIRTUAL_MACHINE,
            ExamViolation::TYPE_NO_FACE,
            ExamViolation::TYPE_MULTIPLE_FACES,
            ExamViolation::TYPE_MULTIPLE_TABS,
            ExamViolation::TYPE_POPUP_BLOCKED,
            ExamViolation::TYPE_EXTERNAL_LINK,
            ExamViolation::TYPE_SUSPICIOUS_AUDIO,
            ExamViolation::TYPE_TIME_MANIPULATION,
            ExamViolation::TYPE_EXTENDED_BLUR,
            ExamViolation::TYPE_PROLONGED_BLUR,
            ExamViolation::TYPE_EXCESSIVE_BLUR,
        ]);
    }

    /**
     * Validate snapshot path to prevent path traversal
     */
    public static function isValidSnapshotPath(string $path): bool
    {
        // Must be in snapshots directory with valid format
        if (! preg_match('/^snapshots\/\d+\/[a-zA-Z0-9_-]+\.(jpg|jpeg|png)$/', $path)) {
            return false;
        }

        // No path traversal
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            return false;
        }

        return true;
    }
}
