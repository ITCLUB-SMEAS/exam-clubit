<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Classroom;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $exams = Exam::with('lesson')->latest()->get();
        $sessions = collect();
        $classrooms = Classroom::all();
        $leaderboard = collect();

        if ($request->exam_id) {
            $sessions = ExamSession::where('exam_id', $request->exam_id)->get();
        }

        if ($request->exam_id) {
            $query = Grade::where('exam_id', $request->exam_id)
                ->whereNotNull('end_time')
                ->with(['student.classroom', 'exam_session']);

            if ($request->session_id) {
                $query->where('exam_session_id', $request->session_id);
            }

            if ($request->classroom_id) {
                $query->whereHas('student', fn($q) => $q->where('classroom_id', $request->classroom_id));
            }

            $leaderboard = $query->orderByDesc('grade')
                ->orderBy('end_time')
                ->limit(100)
                ->get()
                ->map(function ($grade, $index) {
                    return [
                        'rank' => $index + 1,
                        'student_name' => $grade->student->name,
                        'classroom' => $grade->student->classroom->title ?? '-',
                        'grade' => $grade->grade,
                        'status' => $grade->status,
                        'duration' => $this->formatDuration($grade->start_time, $grade->end_time),
                        'attempt' => $grade->attempt_number ?? 1,
                    ];
                });
        }

        return inertia('Admin/Leaderboard/Index', [
            'exams' => $exams,
            'sessions' => $sessions,
            'classrooms' => $classrooms,
            'leaderboard' => $leaderboard,
            'filters' => $request->only(['exam_id', 'session_id', 'classroom_id']),
        ]);
    }

    private function formatDuration($start, $end): string
    {
        if (!$start || !$end) return '-';
        $diff = $start->diff($end);
        return sprintf('%02d:%02d:%02d', $diff->h, $diff->i, $diff->s);
    }
}
