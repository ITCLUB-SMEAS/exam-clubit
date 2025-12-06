<?php

namespace App\Http\Controllers\Student;

use App\Models\Grade;
use App\Models\ExamGroup;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $studentId = auth()->guard("student")->user()->id;

        // Get exam groups with eager loading
        $exam_groups = ExamGroup::with("exam.lesson", "exam_session", "student.classroom")
            ->where("student_id", $studentId)
            ->get();

        // Fetch all grades in ONE query (fix N+1)
        $grades = Grade::where("student_id", $studentId)
            ->whereIn("exam_id", $exam_groups->pluck("exam_id"))
            ->get()
            ->keyBy(fn($g) => $g->exam_id . "_" . $g->exam_session_id);

        $data = [];
        $gradesToCreate = [];

        foreach ($exam_groups as $exam_group) {
            $key = $exam_group->exam_id . "_" . $exam_group->exam_session_id;
            $grade = $grades->get($key);

            if (!$grade) {
                // Collect grades to create (batch insert later)
                $grade = new Grade([
                    'exam_id' => $exam_group->exam_id,
                    'exam_session_id' => $exam_group->exam_session_id,
                    'student_id' => $studentId,
                    'duration' => $exam_group->exam->duration * 60000,
                    'total_correct' => 0,
                    'grade' => 0,
                    'attempt_status' => 'not_started',
                    'attempt_count' => 0,
                ]);
                $gradesToCreate[] = $grade->toArray();
            }

            $data[] = [
                "exam_group" => $exam_group,
                "grade" => $grade,
            ];
        }

        // Batch insert new grades
        if (!empty($gradesToCreate)) {
            Grade::insert($gradesToCreate);
            // Refresh grades for newly created
            $newGrades = Grade::where("student_id", $studentId)
                ->whereIn("exam_id", collect($gradesToCreate)->pluck("exam_id"))
                ->get()
                ->keyBy(fn($g) => $g->exam_id . "_" . $g->exam_session_id);
            
            foreach ($data as &$item) {
                if (!$item['grade']->exists) {
                    $key = $item['exam_group']->exam_id . "_" . $item['exam_group']->exam_session_id;
                    $item['grade'] = $newGrades->get($key) ?? $item['grade'];
                }
            }
        }

        return inertia("Student/Dashboard/Index", [
            "exam_groups" => $data,
        ]);
    }
}
