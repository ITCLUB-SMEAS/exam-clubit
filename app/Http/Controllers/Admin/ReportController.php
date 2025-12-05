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
    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        $exams = Cache::remember('report_exams_list', 300, fn() => 
            Exam::with('lesson:id,title', 'classroom:id,title')
                ->select('id', 'title', 'lesson_id', 'classroom_id')
                ->get()
        );

        return inertia('Admin/Reports/Index', [
            'exams'         => $exams,
            'grades'        => []
        ]);
    }
    
    /**
     * filter
     *
     * @param  mixed $request
     * @return void
     */
    public function filter(Request $request)
    {
        $request->validate([
            'exam_id'       => 'required',
        ]);

        $exams = Cache::remember('report_exams_list', 300, fn() => 
            Exam::with('lesson:id,title', 'classroom:id,title')
                ->select('id', 'title', 'lesson_id', 'classroom_id')
                ->get()
        );

        $exam = Exam::with('lesson:id,title', 'classroom:id,title')
                ->select('id', 'title', 'lesson_id', 'classroom_id')
                ->find($request->exam_id);

        $grades = [];
        if($exam) {
            $exam_session = ExamSession::where('exam_id', $exam->id)
                ->select('id', 'exam_id')
                ->first();

            if ($exam_session) {
                $grades = Grade::with([
                        'student:id,name,nisn,classroom_id',
                        'student.classroom:id,title',
                        'exam:id,title,passing_grade',
                        'exam_session:id,title'
                    ])
                    ->where('exam_id', $exam->id)
                    ->where('exam_session_id', $exam_session->id)
                    ->select('id', 'student_id', 'exam_id', 'exam_session_id', 'grade', 'status', 'start_time', 'end_time')
                    ->get();
            }
        }        
        
        return inertia('Admin/Reports/Index', [
            'exams'         => $exams,
            'grades'        => $grades,
        ]);
    }

    /**
     * export
     *
     * @param  mixed $request
     * @return void
     */
    public function export(Request $request)
    {
        $exam = Exam::with('lesson:id,title', 'classroom:id,title')
                ->find($request->exam_id);

        if (!$exam) {
            return back()->with('error', 'Ujian tidak ditemukan');
        }

        $exam_session = ExamSession::where('exam_id', $exam->id)->first();

        $grades = Grade::with([
                'student:id,name,nisn,classroom_id',
                'student.classroom:id,title',
                'exam:id,title',
                'exam_session:id,title'
            ])
            ->where('exam_id', $exam->id)
            ->where('exam_session_id', $exam_session->id)
            ->get();

        return Excel::download(new GradesExport($grades), 'grade : '.$exam->title.' — '.$exam->lesson->title.' — '.Carbon::now().'.xlsx');
    }
}