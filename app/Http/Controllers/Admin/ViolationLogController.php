<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamViolation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ViolationLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ExamViolation::with(['student', 'exam', 'examSession'])
            ->orderBy('created_at', 'desc');

        // Filter by student
        if ($request->student_id) {
            $query->where('student_id', $request->student_id);
        }

        // Filter by exam
        if ($request->exam_id) {
            $query->where('exam_id', $request->exam_id);
        }

        // Filter by violation type
        if ($request->violation_type) {
            $query->where('violation_type', $request->violation_type);
        }

        $violations = $query->paginate(20)->withQueryString();

        return inertia('Admin/ViolationLogs/Index', [
            'violations' => $violations,
            'filters' => $request->only(['student_id', 'exam_id', 'violation_type']),
            'violationTypes' => ExamViolation::getViolationTypes(),
        ]);
    }

    public function snapshot(ExamViolation $violation)
    {
        if (!$violation->snapshot_path || !Storage::disk('local')->exists($violation->snapshot_path)) {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($violation->snapshot_path));
    }
}
