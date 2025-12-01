<?php

namespace App\Http\Controllers\Admin;

use App\Models\Exam;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\ExamSession;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        // Basic counts
        $students = Student::count();
        $exams = Exam::count();
        $exam_sessions = ExamSession::count();
        $classrooms = Classroom::count();

        // Active sessions today
        $activeSessions = ExamSession::where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->count();

        // Grade distribution for pie chart
        $gradeDistribution = Grade::whereNotNull('end_time')
            ->selectRaw("
                CASE 
                    WHEN grade >= 90 THEN 'A'
                    WHEN grade >= 80 THEN 'B'
                    WHEN grade >= 70 THEN 'C'
                    WHEN grade >= 60 THEN 'D'
                    ELSE 'E'
                END as grade_label,
                COUNT(*) as count
            ")
            ->groupBy('grade_label')
            ->pluck('count', 'grade_label')
            ->toArray();

        // Exam results last 7 days for line chart
        $last7Days = collect(range(6, 0))->map(function ($days) {
            $date = Carbon::now()->subDays($days);
            $count = Grade::whereNotNull('end_time')
                ->whereDate('end_time', $date)
                ->count();
            return [
                'date' => $date->format('d M'),
                'count' => $count,
            ];
        });

        // Pass/Fail ratio
        $passedCount = Grade::where('status', 'passed')->count();
        $failedCount = Grade::where('status', 'failed')->count();

        // Top 5 exams by participants
        $topExams = Exam::withCount(['grades as participants' => function ($q) {
                $q->whereNotNull('end_time');
            }])
            ->orderByDesc('participants')
            ->limit(5)
            ->get(['id', 'title', 'participants']);

        // Recent completed exams
        $recentGrades = Grade::with(['student', 'exam'])
            ->whereNotNull('end_time')
            ->latest('end_time')
            ->limit(5)
            ->get();

        return inertia('Admin/Dashboard/Index', [
            'students' => $students,
            'exams' => $exams,
            'exam_sessions' => $exam_sessions,
            'classrooms' => $classrooms,
            'activeSessions' => $activeSessions,
            'gradeDistribution' => $gradeDistribution,
            'examTrend' => $last7Days,
            'passFailRatio' => ['passed' => $passedCount, 'failed' => $failedCount],
            'topExams' => $topExams,
            'recentGrades' => $recentGrades,
        ]);
    }
}
