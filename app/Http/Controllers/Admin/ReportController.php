<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\ExamSession;
use Illuminate\Http\Request;
use App\Exports\GradesExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Cache;

class ReportController extends Controller
{    
    public function index()
    {
        $exams = Cache::remember('report_exams_list', 300, fn() => 
            Exam::with('lesson:id,title', 'classroom:id,title')
                ->select('id', 'title', 'lesson_id', 'classroom_id')
                ->get()
        );

        return inertia('Admin/Reports/Index', [
            'exams'  => $exams,
            'grades' => []
        ]);
    }
    
    public function filter(Request $request)
    {
        $request->validate([
            'exam_id' => 'required',
        ]);

        $exams = Cache::remember('report_exams_list', 300, fn() => 
            Exam::with('lesson:id,title', 'classroom:id,title')
                ->select('id', 'title', 'lesson_id', 'classroom_id')
                ->get()
        );

        $grades = Grade::with([
                'student:id,name,nisn,classroom_id',
                'student.classroom:id,title',
                'exam:id,title,passing_grade,lesson_id',
                'exam.lesson:id,title',
                'exam_session:id,title'
            ])
            ->where('exam_id', $request->exam_id)
            ->select('id', 'student_id', 'exam_id', 'exam_session_id', 'grade', 'status', 'start_time', 'end_time')
            ->get();
        
        return inertia('Admin/Reports/Index', [
            'exams'  => $exams,
            'grades' => $grades,
        ]);
    }

    public function export(Request $request)
    {
        $exam = Exam::with('lesson:id,title')->find($request->exam_id);

        if (!$exam) {
            return back()->with('error', 'Ujian tidak ditemukan');
        }

        $grades = Grade::with([
                'student:id,name,nisn,classroom_id',
                'student.classroom:id,title',
                'exam:id,title,lesson_id',
                'exam.lesson:id,title',
                'exam_session:id,title'
            ])
            ->where('exam_id', $exam->id)
            ->get();

        if ($grades->isEmpty()) {
            return back()->with('error', 'Tidak ada data nilai untuk diexport');
        }

        $filename = "Nilai_{$exam->title}_{$exam->lesson->title}_" . Carbon::now()->format('Y-m-d') . ".xlsx";
        
        return Excel::download(new GradesExport($grades), $filename);
    }
}