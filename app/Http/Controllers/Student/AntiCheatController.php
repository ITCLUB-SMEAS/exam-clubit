<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamGroup;
use App\Models\ExamViolation;
use App\Models\Grade;
use App\Services\AntiCheatService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AntiCheatController extends Controller
{
    /**
     * Record a violation from the frontend
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recordViolation(Request $request): JsonResponse
    {
        $request->validate([
            'exam_id' => 'required|integer|exists:exams,id',
            'exam_session_id' => 'required|integer|exists:exam_sessions,id',
            'grade_id' => 'required|integer|exists:grades,id',
            'violation_type' => 'required|string|max:50',
            'description' => 'nullable|string|max:500',
            'metadata' => 'nullable|array',
            'snapshot' => 'nullable|string', // Base64 image
        ]);

        $student = auth()->guard('student')->user();

        // Verify this is the student's exam
        $grade = Grade::where('id', $request->grade_id)
            ->where('student_id', $student->id)
            ->where('exam_id', $request->exam_id)
            ->where('exam_session_id', $request->exam_session_id)
            ->whereNull('end_time')
            ->first();

        if (!$grade) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid exam session or exam already ended.',
            ], 400);
        }

        // Get the exam
        $exam = Exam::find($request->exam_id);

        if (!$exam || !$exam->anticheat_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Anti-cheat is not enabled for this exam.',
            ], 400);
        }

        // Validate violation type
        if (!AntiCheatService::isValidViolationType($request->violation_type)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid violation type.',
            ], 400);
        }

        // Save snapshot if provided
        $snapshotPath = null;
        if ($request->snapshot) {
            $snapshotPath = $this->saveSnapshot($request->snapshot, $student->id, $request->exam_id);
        }

        // Record the violation
        $violation = AntiCheatService::recordViolation(
            $student,
            $exam,
            $request->exam_session_id,
            $grade,
            $request->violation_type,
            $request->description,
            $request->metadata,
            $snapshotPath
        );

        // Check if auto-submit should be triggered
        $shouldAutoSubmit = false;
        if ($exam->auto_submit_on_max_violations && AntiCheatService::hasExceededLimit($grade, $exam)) {
            $shouldAutoSubmit = true;
        }

        // Check if student got blocked
        $student->refresh();
        $isBlocked = $student->is_blocked;

        // Get current violation status
        $remainingViolations = AntiCheatService::getRemainingViolations($grade, $exam);
        $warningReached = AntiCheatService::hasReachedWarningThreshold($grade, $exam);

        return response()->json([
            'success' => true,
            'message' => 'Violation recorded.',
            'data' => [
                'violation_id' => $violation->id,
                'total_violations' => $grade->fresh()->violation_count,
                'remaining_violations' => $remainingViolations,
                'warning_reached' => $warningReached,
                'should_auto_submit' => $shouldAutoSubmit,
                'max_violations' => $exam->max_violations ?? 10,
                'is_blocked' => $isBlocked,
            ],
        ]);
    }

    /**
     * Record multiple violations at once (batch)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recordBatchViolations(Request $request): JsonResponse
    {
        $request->validate([
            'exam_id' => 'required|integer|exists:exams,id',
            'exam_session_id' => 'required|integer|exists:exam_sessions,id',
            'grade_id' => 'required|integer|exists:grades,id',
            'violations' => 'required|array|min:1',
            'violations.*.type' => 'required|string|max:50',
            'violations.*.description' => 'nullable|string|max:500',
            'violations.*.metadata' => 'nullable|array',
        ]);

        $student = auth()->guard('student')->user();

        // Verify this is the student's exam
        $grade = Grade::where('id', $request->grade_id)
            ->where('student_id', $student->id)
            ->where('exam_id', $request->exam_id)
            ->where('exam_session_id', $request->exam_session_id)
            ->whereNull('end_time')
            ->first();

        if (!$grade) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid exam session or exam already ended.',
            ], 400);
        }

        // Get the exam
        $exam = Exam::find($request->exam_id);

        if (!$exam || !$exam->anticheat_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Anti-cheat is not enabled for this exam.',
            ], 400);
        }

        // Validate all violation types
        foreach ($request->violations as $violation) {
            if (!AntiCheatService::isValidViolationType($violation['type'])) {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid violation type: {$violation['type']}",
                ], 400);
            }
        }

        // Record all violations
        $results = AntiCheatService::recordBatchViolations(
            $student,
            $exam,
            $request->exam_session_id,
            $grade,
            $request->violations
        );

        // Refresh grade to get updated counts
        $grade->refresh();

        // Check if auto-submit should be triggered
        $shouldAutoSubmit = false;
        if ($exam->auto_submit_on_max_violations && AntiCheatService::hasExceededLimit($grade, $exam)) {
            $shouldAutoSubmit = true;
        }

        return response()->json([
            'success' => true,
            'message' => count($results) . ' violations recorded.',
            'data' => [
                'violations_recorded' => count($results),
                'total_violations' => $grade->violation_count,
                'remaining_violations' => AntiCheatService::getRemainingViolations($grade, $exam),
                'warning_reached' => AntiCheatService::hasReachedWarningThreshold($grade, $exam),
                'should_auto_submit' => $shouldAutoSubmit,
                'max_violations' => $exam->max_violations ?? 10,
            ],
        ]);
    }

    /**
     * Get current violation status for a grade
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getViolationStatus(Request $request): JsonResponse
    {
        $request->validate([
            'grade_id' => 'required|integer|exists:grades,id',
        ]);

        $student = auth()->guard('student')->user();

        $grade = Grade::where('id', $request->grade_id)
            ->where('student_id', $student->id)
            ->first();

        if (!$grade) {
            return response()->json([
                'success' => false,
                'message' => 'Grade not found.',
            ], 404);
        }

        $exam = Exam::find($grade->exam_id);

        return response()->json([
            'success' => true,
            'data' => [
                'total_violations' => $grade->violation_count,
                'max_violations' => $exam->max_violations ?? 10,
                'remaining_violations' => AntiCheatService::getRemainingViolations($grade, $exam),
                'warning_reached' => AntiCheatService::hasReachedWarningThreshold($grade, $exam),
                'limit_exceeded' => AntiCheatService::hasExceededLimit($grade, $exam),
                'is_flagged' => $grade->is_flagged,
                'summary' => $grade->getViolationsSummary(),
            ],
        ]);
    }

    /**
     * Get anti-cheat configuration for an exam
     *
     * @param int $examId
     * @return JsonResponse
     */
    public function getConfig(int $examId): JsonResponse
    {
        $student = auth()->guard('student')->user();

        // Verify student is enrolled in an exam group for this exam
        $examGroup = ExamGroup::where('student_id', $student->id)
            ->whereHas('exam', function ($query) use ($examId) {
                $query->where('id', $examId);
            })
            ->first();

        if (!$examGroup) {
            return response()->json([
                'success' => false,
                'message' => 'You are not enrolled in this exam.',
            ], 403);
        }

        $exam = Exam::find($examId);

        if (!$exam) {
            return response()->json([
                'success' => false,
                'message' => 'Exam not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => AntiCheatService::getAntiCheatConfig($exam),
        ]);
    }

    /**
     * Heartbeat endpoint to verify exam is still active
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $request->validate([
            'grade_id' => 'required|integer|exists:grades,id',
            'timestamp' => 'nullable|integer',
        ]);

        $student = auth()->guard('student')->user();

        $grade = Grade::where('id', $request->grade_id)
            ->where('student_id', $student->id)
            ->whereNull('end_time')
            ->first();

        if (!$grade) {
            return response()->json([
                'success' => false,
                'message' => 'Exam session not found or already ended.',
                'should_redirect' => true,
            ], 400);
        }

        $exam = Exam::find($grade->exam_id);

        // Check if max violations exceeded and should auto-submit
        $shouldAutoSubmit = false;
        if ($exam && $exam->auto_submit_on_max_violations && AntiCheatService::hasExceededLimit($grade, $exam)) {
            $shouldAutoSubmit = true;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'exam_active' => true,
                'duration_remaining' => $grade->duration,
                'total_violations' => $grade->violation_count,
                'should_auto_submit' => $shouldAutoSubmit,
                'server_time' => Carbon::now()->timestamp,
            ],
        ]);
    }

    /**
     * Get server time for time anomaly detection
     *
     * @return JsonResponse
     */
    public function serverTime(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'server_time' => (int) (microtime(true) * 1000), // milliseconds
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Save snapshot from base64 image
     */
    protected function saveSnapshot(string $base64Image, int $studentId, int $examId): ?string
    {
        try {
            // Remove data URL prefix if present
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
                $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
            }

            $imageData = base64_decode($base64Image);
            if ($imageData === false) return null;

            // Validate it's actually an image (check magic bytes)
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageData);
            if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'])) {
                return null;
            }

            // Limit size to 100KB
            if (strlen($imageData) > 102400) {
                return null;
            }

            $filename = sprintf('violations/%d/%d_%s.jpg', $examId, $studentId, now()->format('Ymd_His'));
            Storage::disk('local')->put($filename, $imageData);

            return $filename;
        } catch (\Exception $e) {
            return null;
        }
    }
}
