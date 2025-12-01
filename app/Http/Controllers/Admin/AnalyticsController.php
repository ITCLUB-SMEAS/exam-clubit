<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        // Overall statistics
        $totalExams = Exam::count();
        $totalStudents = Student::count();
        $totalGrades = Grade::whereNotNull('end_time')->count();
        
        $avgGrade = Grade::whereNotNull('end_time')->avg('grade') ?? 0;
        $passedCount = Grade::where('status', 'passed')->count();
        $failedCount = Grade::where('status', 'failed')->count();
        $passRate = $totalGrades > 0 ? round(($passedCount / $totalGrades) * 100, 1) : 0;

        // Recent exams performance
        $recentExams = Exam::with('lesson')
            ->withCount(['grades as completed_count' => function($q) {
                $q->whereNotNull('end_time');
            }])
            ->withAvg(['grades as avg_grade' => function($q) {
                $q->whereNotNull('end_time');
            }], 'grade')
            ->latest()
            ->limit(5)
            ->get();

        // Grade distribution
        $gradeDistribution = Grade::whereNotNull('end_time')
            ->selectRaw("
                CASE 
                    WHEN grade >= 90 THEN 'A (90-100)'
                    WHEN grade >= 80 THEN 'B (80-89)'
                    WHEN grade >= 70 THEN 'C (70-79)'
                    WHEN grade >= 60 THEN 'D (60-69)'
                    ELSE 'E (<60)'
                END as grade_range,
                COUNT(*) as count
            ")
            ->groupBy('grade_range')
            ->orderByRaw("MIN(grade) DESC")
            ->get();

        // Performance by classroom
        $classroomPerformance = Classroom::withCount('students')
            ->get()
            ->map(function($classroom) {
                $studentIds = $classroom->students->pluck('id');
                $grades = Grade::whereIn('student_id', $studentIds)->whereNotNull('end_time');
                return [
                    'name' => $classroom->title,
                    'students_count' => $classroom->students_count,
                    'avg_grade' => round($grades->avg('grade') ?? 0, 1),
                    'exams_taken' => $grades->count(),
                ];
            });

        return inertia('Admin/Analytics/Index', [
            'stats' => [
                'total_exams' => $totalExams,
                'total_students' => $totalStudents,
                'total_completed' => $totalGrades,
                'avg_grade' => round($avgGrade, 1),
                'pass_rate' => $passRate,
                'passed_count' => $passedCount,
                'failed_count' => $failedCount,
            ],
            'recentExams' => $recentExams,
            'gradeDistribution' => $gradeDistribution,
            'classroomPerformance' => $classroomPerformance,
        ]);
    }

    public function examDetail(Exam $exam)
    {
        $exam->load('lesson', 'classroom', 'questions');

        // Exam statistics
        $grades = Grade::where('exam_id', $exam->id)->whereNotNull('end_time')->get();
        $stats = [
            'total_participants' => $grades->count(),
            'avg_grade' => round($grades->avg('grade') ?? 0, 1),
            'max_grade' => $grades->max('grade') ?? 0,
            'min_grade' => $grades->min('grade') ?? 0,
            'passed' => $grades->where('status', 'passed')->count(),
            'failed' => $grades->where('status', 'failed')->count(),
        ];

        // Question analysis
        $questionStats = $exam->questions->map(function($question) use ($exam) {
            $answers = Answer::where('question_id', $question->id)
                ->where('exam_id', $exam->id)
                ->get();
            
            $total = $answers->count();
            $correct = $answers->where('is_correct', 'Y')->count();
            $difficulty = $total > 0 ? round(($correct / $total) * 100, 1) : 0;

            // Difficulty level
            $level = 'Sedang';
            if ($difficulty >= 70) $level = 'Mudah';
            elseif ($difficulty < 40) $level = 'Sulit';

            return [
                'id' => $question->id,
                'question' => strip_tags(substr($question->question, 0, 100)) . '...',
                'type' => $question->question_type,
                'total_answers' => $total,
                'correct_answers' => $correct,
                'difficulty_percent' => $difficulty,
                'difficulty_level' => $level,
            ];
        });

        // Top performers
        $topPerformers = Grade::where('exam_id', $exam->id)
            ->whereNotNull('end_time')
            ->with('student')
            ->orderByDesc('grade')
            ->limit(10)
            ->get()
            ->map(fn($g) => [
                'name' => $g->student->name,
                'grade' => $g->grade,
                'status' => $g->status,
            ]);

        return inertia('Admin/Analytics/ExamDetail', [
            'exam' => $exam,
            'stats' => $stats,
            'questionStats' => $questionStats,
            'topPerformers' => $topPerformers,
        ]);
    }

    public function studentPerformance(Request $request)
    {
        $query = Student::with('classroom');

        if ($request->classroom_id) {
            $query->where('classroom_id', $request->classroom_id);
        }

        $students = $query->get()->map(function($student) {
            $grades = Grade::where('student_id', $student->id)->whereNotNull('end_time');
            return [
                'id' => $student->id,
                'name' => $student->name,
                'nisn' => $student->nisn,
                'classroom' => $student->classroom->title ?? '-',
                'exams_taken' => $grades->count(),
                'avg_grade' => round($grades->avg('grade') ?? 0, 1),
                'passed' => $grades->clone()->where('status', 'passed')->count(),
                'failed' => $grades->clone()->where('status', 'failed')->count(),
            ];
        })->sortByDesc('avg_grade')->values();

        $classrooms = Classroom::all();

        return inertia('Admin/Analytics/StudentPerformance', [
            'students' => $students,
            'classrooms' => $classrooms,
            'selectedClassroom' => $request->classroom_id,
        ]);
    }
}
