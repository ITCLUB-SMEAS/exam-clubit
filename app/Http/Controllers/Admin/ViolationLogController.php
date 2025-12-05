<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamViolation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class ViolationLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ExamViolation::with([
                'student:id,name,nisn',
                'exam:id,title',
                'examSession:id,title'
            ])
            ->select('id', 'student_id', 'exam_id', 'exam_session_id', 'violation_type', 'description', 'ip_address', 'snapshot_path', 'created_at')
            ->orderBy('created_at', 'desc');

        if ($request->student_id) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->exam_id) {
            $query->where('exam_id', $request->exam_id);
        }

        if ($request->violation_type) {
            $query->where('violation_type', $request->violation_type);
        }

        $violations = $query->paginate(20)->withQueryString();

        // Cache violation types
        $violationTypes = Cache::remember('violation_types', 3600, fn() => ExamViolation::getViolationTypes());

        return inertia('Admin/ViolationLogs/Index', [
            'violations' => $violations,
            'filters' => $request->only(['student_id', 'exam_id', 'violation_type']),
            'violationTypes' => $violationTypes,
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
