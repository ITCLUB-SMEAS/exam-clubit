<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\Answer;
use App\Models\ExamViolation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ExamMonitorController extends Controller
{
    public function index()
    {
        $activeSessions = Cache::remember('active_exam_sessions', 10, fn() =>
            ExamSession::with(['exam.lesson', 'exam.classroom'])
                ->whereHas('exam')
                ->where('start_time', '<=', now())
                ->where('end_time', '>=', now())
                ->get()
        );

        return Inertia::render('Admin/Monitor/Index', [
            'activeSessions' => $activeSessions,
        ]);
    }

    public function show(ExamSession $examSession)
    {
        $examSession->load(['exam.lesson', 'exam.classroom']);

        if (!$examSession->exam) {
            return redirect()->route('admin.monitor.index')
                ->with('error', 'Ujian tidak ditemukan.');
        }

        return Inertia::render('Admin/Monitor/Show', [
            'examSession' => $examSession,
            'participants' => $this->getParticipants($examSession),
        ]);
    }

    public function participants(ExamSession $examSession)
    {
        // Cache for 5 seconds to reduce DB load during polling
        $cacheKey = "monitor_participants_{$examSession->id}";
        
        $data = Cache::remember($cacheKey, 5, fn() => [
            'participants' => $this->getParticipants($examSession),
            'stats' => $this->getSessionStats($examSession),
            'cached_at' => now()->toIso8601String(),
        ]);

        return response()->json($data);
    }

    protected function getParticipants(ExamSession $examSession): array
    {
        $grades = Grade::with(['student.classroom'])
            ->where('exam_session_id', $examSession->id)
            ->get();

        // Batch calculate progress for all students
        $progressData = $this->batchCalculateProgress($grades, $examSession->exam_id);

        return $grades->map(function ($grade) use ($progressData) {
            $key = $grade->student_id;
            $progress = $progressData[$key] ?? 0;
            
            if (!$grade->start_time) $progress = 0;
            if ($grade->end_time) $progress = 100;
            
            return [
                'id' => $grade->id,
                'student' => [
                    'id' => $grade->student->id,
                    'name' => $grade->student->name,
                    'nisn' => $grade->student->nisn,
                    'classroom' => $grade->student->classroom?->title ?? $grade->student->classroom?->name,
                ],
                'status' => $this->getStatus($grade),
                'progress' => $progress,
                'duration_remaining' => $grade->duration,
                'violation_count' => $grade->violation_count,
                'is_flagged' => $grade->is_flagged,
                'is_paused' => $grade->is_paused,
                'start_time' => $grade->start_time?->format('H:i:s'),
                'end_time' => $grade->end_time?->format('H:i:s'),
                'last_activity' => $grade->updated_at->diffForHumans(),
            ];
        })
        ->sortBy('student.name')
        ->values()
        ->all();
    }

    protected function batchCalculateProgress($grades, int $examId): array
    {
        $studentIds = $grades->pluck('student_id')->toArray();
        
        if (empty($studentIds)) return [];

        // Get total and answered counts in 2 queries instead of N*2
        $totals = Answer::where('exam_id', $examId)
            ->whereIn('student_id', $studentIds)
            ->selectRaw('student_id, COUNT(*) as total')
            ->groupBy('student_id')
            ->pluck('total', 'student_id')
            ->toArray();

        $answered = Answer::where('exam_id', $examId)
            ->whereIn('student_id', $studentIds)
            ->where(function ($q) {
                $q->where('answer', '!=', 0)
                  ->orWhereNotNull('answer_text')
                  ->orWhereNotNull('answer_options');
            })
            ->selectRaw('student_id, COUNT(*) as answered')
            ->groupBy('student_id')
            ->pluck('answered', 'student_id')
            ->toArray();

        $result = [];
        foreach ($studentIds as $sid) {
            $total = $totals[$sid] ?? 0;
            $ans = $answered[$sid] ?? 0;
            $result[$sid] = $total > 0 ? round(($ans / $total) * 100) : 0;
        }

        return $result;
    }

    protected function getStatus(Grade $grade): string
    {
        if ($grade->end_time) return 'completed';
        if ($grade->is_paused) return 'paused';
        if ($grade->start_time) return 'in_progress';
        return 'not_started';
    }

    protected function getSessionStats(ExamSession $examSession): array
    {
        $grades = Grade::where('exam_session_id', $examSession->id)->get();

        return [
            'total' => $grades->count(),
            'not_started' => $grades->whereNull('start_time')->count(),
            'in_progress' => $grades->whereNotNull('start_time')->whereNull('end_time')->where('is_paused', false)->count(),
            'paused' => $grades->where('is_paused', true)->count(),
            'completed' => $grades->whereNotNull('end_time')->count(),
            'flagged' => $grades->where('is_flagged', true)->count(),
            'violations' => ExamViolation::where('exam_session_id', $examSession->id)->count(),
        ];
    }

    public function violations(ExamSession $examSession)
    {
        $violations = ExamViolation::with('student')
            ->where('exam_session_id', $examSession->id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn($v) => [
                'id' => $v->id,
                'student_name' => $v->student->name,
                'type' => $v->violation_type,
                'description' => $v->description,
                'created_at' => $v->created_at->format('H:i:s'),
            ]);

        return response()->json(['violations' => $violations]);
    }
}
