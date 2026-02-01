<?php

namespace App\Http\Controllers\Admin;

use App\Exports\GradesExport;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Grade;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index()
    {
        $exams = Cache::remember('report_exams_list', 300, fn () => Exam::with('lesson:id,title', 'classroom:id,title')
            ->select('id', 'title', 'lesson_id', 'classroom_id')
            ->get()
        );

        return inertia('Admin/Reports/Index', [
            'exams' => $exams,
            'grades' => [],
        ]);
    }

    public function filter(Request $request)
    {
        $request->validate([
            'exam_id' => 'required',
        ]);

        $exams = Cache::remember('report_exams_list', 300, fn () => Exam::with('lesson:id,title', 'classroom:id,title')
            ->select('id', 'title', 'lesson_id', 'classroom_id')
            ->get()
        );

        $grades = Grade::with([
            'student:id,name,nisn,classroom_id',
            'student.classroom:id,title',
            'exam:id,title,passing_grade,lesson_id',
            'exam.lesson:id,title',
            'exam_session:id,title',
        ])
            ->where('exam_id', $request->exam_id)
            ->select('id', 'student_id', 'exam_id', 'exam_session_id', 'grade', 'status', 'start_time', 'end_time')
            ->orderBy('grade', 'desc')
            ->paginate(25)
            ->withQueryString();

        return inertia('Admin/Reports/Index', [
            'exams' => $exams,
            'grades' => $grades,
        ]);
    }

    public function export(Request $request)
    {
        $exam = Exam::with('lesson:id,title')->find($request->exam_id);

        if (! $exam) {
            return back()->with('error', 'Ujian tidak ditemukan');
        }

        // Check if there are any grades first (lightweight query)
        $hasGrades = Grade::where('exam_id', $exam->id)->exists();

        if (! $hasGrades) {
            return back()->with('error', 'Tidak ada data nilai untuk diexport');
        }

        $filename = "Nilai_{$exam->title}_{$exam->lesson->title}_".Carbon::now()->format('Y-m-d').'.xlsx';

        // Pass exam_id to export class - it will handle the query with cursor
        return Excel::download(new GradesExport($exam->id), $filename);
    }
}
