<?php

namespace App\Http\Controllers\Admin;

use App\Models\Exam;
use App\Models\Student;
use App\Models\ExamGroup;
use App\Models\ExamSession;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesTransactions;
use Illuminate\Support\Facades\Cache;

class ExamSessionController extends Controller
{
    use HandlesTransactions;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //get exam_sessions (only those with existing exam)
        $exam_sessions = ExamSession::whereHas('exam')
            ->when(request()->q, function($exam_sessions) {
                $exam_sessions = $exam_sessions->where('title', 'like', '%'. request()->q . '%');
            })->with('exam.classroom', 'exam.lesson', 'exam_groups')->latest()->paginate(5);

        //append query string to pagination links
        $exam_sessions->appends(['q' => request()->q]);

        //render with inertia
        return inertia('Admin/ExamSessions/Index', [
            'exam_sessions' => $exam_sessions,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $exams = Cache::remember('exams_dropdown', 300, fn() => 
            Exam::select('id', 'title')->get()
        );
        
        return inertia('Admin/ExamSessions/Create', [
            'exams' => $exams,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //validate request
        $request->validate([
            'title'         => 'required',
            'exam_id'       => 'required',
            'start_time'    => 'required',
            'end_time'      => 'required',
        ]);

        $startTime = date('Y-m-d H:i:s', strtotime($request->start_time));
        $endTime = date('Y-m-d H:i:s', strtotime($request->end_time));

        // Check for scheduling conflicts
        $exam = Exam::findOrFail($request->exam_id);
        $conflicts = $this->checkScheduleConflicts($exam->classroom_id, $startTime, $endTime);
        
        if ($conflicts->isNotEmpty()) {
            $conflictList = $conflicts->map(fn($c) => $c->title . ' (' . $c->start_time->format('H:i') . '-' . $c->end_time->format('H:i') . ')')->join(', ');
            return back()->withErrors(['start_time' => "Jadwal bentrok dengan: {$conflictList}"])->withInput();
        }

        //create exam_session
        ExamSession::create([
            'title'         => $request->title,
            'exam_id'       => $request->exam_id,
            'start_time'    => $startTime,
            'end_time'      => $endTime,
        ]);

        //redirect
        return redirect()->route('admin.exam_sessions.index');
    }

    /**
     * Check for scheduling conflicts in the same classroom
     */
    protected function checkScheduleConflicts(int $classroomId, string $startTime, string $endTime, ?int $excludeId = null)
    {
        return ExamSession::whereHas('exam', fn($q) => $q->where('classroom_id', $classroomId))
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                  ->orWhereBetween('end_time', [$startTime, $endTime])
                  ->orWhere(function ($q2) use ($startTime, $endTime) {
                      $q2->where('start_time', '<=', $startTime)
                         ->where('end_time', '>=', $endTime);
                  });
            })
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->get();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $exam_session
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //get exam_session
        $exam_session = ExamSession::with('exam.classroom', 'exam.lesson')->findOrFail($id);

        //get relation exam_groups with pagination
        $exam_session->setRelation('exam_groups', $exam_session->exam_groups()->with('student.classroom')->paginate(5));

        // Get all classrooms for bulk enrollment
        $classrooms = \App\Models\Classroom::withCount(['students' => function ($q) use ($exam_session) {
            // Count students not yet enrolled
            $enrolledIds = ExamGroup::where('exam_session_id', $exam_session->id)->pluck('student_id');
            $q->whereNotIn('id', $enrolledIds);
        }])->get();

        // Get enrolled count per classroom
        $enrolledByClass = ExamGroup::where('exam_session_id', $exam_session->id)
            ->join('students', 'exam_groups.student_id', '=', 'students.id')
            ->selectRaw('students.classroom_id, COUNT(*) as count')
            ->groupBy('students.classroom_id')
            ->pluck('count', 'classroom_id');

        // Check if all active exams are paused
        $activeGrades = \App\Models\Grade::where('exam_session_id', $id)
            ->whereNull('end_time')->count();
        $pausedGrades = \App\Models\Grade::where('exam_session_id', $id)
            ->whereNull('end_time')->where('is_paused', true)->count();
        $allPaused = $activeGrades > 0 && $activeGrades === $pausedGrades;

        //render with inertia
        return inertia('Admin/ExamSessions/Show', [
            'exam_session' => $exam_session,
            'classrooms' => $classrooms,
            'enrolledByClass' => $enrolledByClass,
            'allPaused' => $allPaused,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $exam_session = ExamSession::findOrFail($id);
        
        $exams = Cache::remember('exams_dropdown', 300, fn() => 
            Exam::select('id', 'title')->get()
        );
        
        return inertia('Admin/ExamSessions/Edit', [
            'exam_session'  => $exam_session,
            'exams'         => $exams,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ExamSession $exam_session)
    {
        //validate request
        $request->validate([
            'title'         => 'required',
            'exam_id'       => 'required',
            'start_time'    => 'required',
            'end_time'      => 'required',
        ]);

        $startTime = date('Y-m-d H:i:s', strtotime($request->start_time));
        $endTime = date('Y-m-d H:i:s', strtotime($request->end_time));

        // Check for scheduling conflicts (exclude current session)
        $exam = Exam::findOrFail($request->exam_id);
        $conflicts = $this->checkScheduleConflicts($exam->classroom_id, $startTime, $endTime, $exam_session->id);
        
        if ($conflicts->isNotEmpty()) {
            $conflictList = $conflicts->map(fn($c) => $c->title . ' (' . $c->start_time->format('H:i') . '-' . $c->end_time->format('H:i') . ')')->join(', ');
            return back()->withErrors(['start_time' => "Jadwal bentrok dengan: {$conflictList}"])->withInput();
        }
        
        //update exam_session
        $exam_session->update([
            'title'         => $request->title,
            'exam_id'       => $request->exam_id,
            'start_time'    => $startTime,
            'end_time'      => $endTime,
        ]);
        
        //redirect
        return redirect()->route('admin.exam_sessions.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //get exam_session
        $exam_session = ExamSession::findOrFail($id);
        
        //delete exam_session
        $exam_session->delete();
        
        //redirect
        return redirect()->route('admin.exam_sessions.index');
    }

    /**
     * createEnrolle
     *
     * @param  mixed $exam_session
     * @return void
     */
    public function createEnrolle(ExamSession $exam_session)
    {
        //get exams
        $exam = $exam_session->exam;

        //get students already enrolled
        $students_enrolled = ExamGroup::where('exam_id', $exam->id)->where('exam_session_id', $exam_session->id)->pluck('student_id')->all();
        
        //get students
        $students = Student::with('classroom')->where('classroom_id', $exam->classroom_id)->whereNotIn('id', $students_enrolled)->get();

        //render with inertia
        return inertia('Admin/ExamGroups/Create', [
            'exam'          => $exam,
            'exam_session'  => $exam_session,
            'students'      => $students,
        ]);
    }

    /**
     * storeEnrolle
     *
     * @param  mixed $exam_session
     * @return void
     */
    public function storeEnrolle(Request $request, ExamSession $exam_session)
    {
        $request->validate([
            'student_id'    => 'required|array|max:100',
            'student_id.*'  => 'exists:students,id',
        ]);
        
        return $this->executeInTransaction(function () use ($request, $exam_session) {
            $exam = $exam_session->exam;
            $studentIds = $request->student_id;
            
            // Get existing enrollments in ONE query
            $existingEnrollments = ExamGroup::where('exam_id', $exam->id)
                ->where('exam_session_id', $exam_session->id)
                ->whereIn('student_id', $studentIds)
                ->pluck('student_id')
                ->toArray();
            
            // Filter out already enrolled students
            $newStudentIds = array_diff($studentIds, $existingEnrollments);
            
            // Batch insert new enrollments
            if (!empty($newStudentIds)) {
                $enrollments = array_map(fn($sid) => [
                    'exam_id' => $exam->id,
                    'exam_session_id' => $exam_session->id,
                    'student_id' => $sid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $newStudentIds);
                
                ExamGroup::insert($enrollments);
            }
            
            return redirect()->route('admin.exam_sessions.show', $exam_session->id);
        }, 'Gagal mendaftarkan siswa. Silakan coba lagi.');
    }

    /**
     * destroyEnrolle
     *
     * @param  mixed $exam_session
     * @param  mixed $exam_group
     * @return void
     */
    public function destroyEnrolle(ExamSession $exam_session, ExamGroup $exam_group)
    {
        //delete exam_group
        $exam_group->delete();
        
        //redirect
        return redirect()->route('admin.exam_sessions.show', $exam_session->id);
    }

    /**
     * Enroll entire class at once
     */
    public function bulkEnrollClass(Request $request, ExamSession $exam_session)
    {
        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
        ]);

        $exam = $exam_session->exam;
        
        // Verify classroom is allowed for this exam (must match exam's classroom)
        if ($exam->classroom_id && $exam->classroom_id != $request->classroom_id) {
            $allowedClass = \App\Models\Classroom::find($exam->classroom_id)?->title ?? 'Unknown';
            return back()->with('error', "Ujian ini hanya untuk kelas \"{$allowedClass}\". Silakan pilih kelas yang sesuai.");
        }

        return $this->executeInTransaction(function () use ($request, $exam_session, $exam) {
            // Get students already enrolled
            $enrolledIds = ExamGroup::where('exam_id', $exam->id)
                ->where('exam_session_id', $exam_session->id)
                ->pluck('student_id')
                ->toArray();

            // Get students from selected class not yet enrolled
            $students = Student::where('classroom_id', $request->classroom_id)
                ->whereNotIn('id', $enrolledIds)
                ->pluck('id')
                ->toArray();

            if (empty($students)) {
                return back()->with('info', 'Semua siswa di kelas ini sudah terdaftar.');
            }

            // Batch insert
            $enrollments = array_map(fn($sid) => [
                'exam_id' => $exam->id,
                'exam_session_id' => $exam_session->id,
                'student_id' => $sid,
                'created_at' => now(),
                'updated_at' => now(),
            ], $students);

            ExamGroup::insert($enrollments);

            return redirect()->route('admin.exam_sessions.show', $exam_session->id)
                ->with('success', count($students) . " siswa berhasil didaftarkan.");
        }, 'Gagal mendaftarkan siswa. Silakan coba lagi.');
    }

    /**
     * Remove all enrollments from a class
     */
    public function bulkUnenrollClass(Request $request, ExamSession $exam_session)
    {
        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
        ]);

        $studentIds = Student::where('classroom_id', $request->classroom_id)->pluck('id');

        $deleted = ExamGroup::where('exam_session_id', $exam_session->id)
            ->whereIn('student_id', $studentIds)
            ->delete();

        return redirect()->route('admin.exam_sessions.show', $exam_session->id)
            ->with('success', "{$deleted} siswa berhasil dihapus dari sesi.");
    }
}