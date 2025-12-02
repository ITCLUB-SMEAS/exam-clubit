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
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        // Cache stats for 5 minutes
        $stats = Cache::remember('dashboard_stats', 300, function () {
            return [
                'students' => Student::count(),
                'exams' => Exam::count(),
                'exam_sessions' => ExamSession::count(),
                'classrooms' => Classroom::count(),
                'passedCount' => Grade::where('status', 'passed')->count(),
                'failedCount' => Grade::where('status', 'failed')->count(),
            ];
        });

        // Active sessions - no cache (real-time)
        $activeSessions = ExamSession::where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->count();

        // Cache grade distribution for 10 minutes
        $gradeDistribution = Cache::remember('grade_distribution', 600, function () {
            return Grade::whereNotNull('end_time')
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
        });

        // Cache exam trend for 5 minutes
        $last7Days = Cache::remember('exam_trend_7days', 300, function () {
            return collect(range(6, 0))->map(function ($days) {
                $date = Carbon::now()->subDays($days);
                return [
                    'date' => $date->format('d M'),
                    'count' => Grade::whereNotNull('end_time')
                        ->whereDate('end_time', $date)
                        ->count(),
                ];
            });
        });

        // Cache top exams for 10 minutes
        $topExams = Cache::remember('top_exams', 600, function () {
            return Exam::withCount(['grades as participants' => fn($q) => $q->whereNotNull('end_time')])
                ->orderByDesc('participants')
                ->limit(5)
                ->get(['id', 'title']);
        });

        // Recent grades - short cache 1 minute
        $recentGrades = Cache::remember('recent_grades', 60, function () {
            return Grade::with(['student:id,name', 'exam:id,title'])
                ->whereNotNull('end_time')
                ->latest('end_time')
                ->limit(5)
                ->get();
        });

        return inertia('Admin/Dashboard/Index', [
            'students' => $stats['students'],
            'exams' => $stats['exams'],
            'exam_sessions' => $stats['exam_sessions'],
            'classrooms' => $stats['classrooms'],
            'activeSessions' => $activeSessions,
            'gradeDistribution' => $gradeDistribution,
            'examTrend' => $last7Days,
            'passFailRatio' => ['passed' => $stats['passedCount'], 'failed' => $stats['failedCount']],
            'topExams' => $topExams,
            'recentGrades' => $recentGrades,
        ]);
    }
}
