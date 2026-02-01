<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GradeResource;
use App\Models\Grade;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Grade::with('student', 'exam')
            ->whereNotNull('end_time');

        // Non-admin can only see limited data
        if (! $user->isAdmin()) {
            $query->select(['id', 'exam_id', 'student_id', 'grade', 'status', 'end_time']);
        }

        $grades = $query
            ->when($request->exam_id, fn ($q) => $q->where('exam_id', $request->exam_id))
            ->when($request->student_id, fn ($q) => $q->where('student_id', $request->student_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->paginate($request->per_page ?? 15);

        return GradeResource::collection($grades);
    }

    public function show(Grade $grade)
    {
        $user = request()->user();

        // Hide sensitive data for non-admin
        if (! $user->isAdmin()) {
            return new GradeResource($grade);
        }

        return new GradeResource($grade->load('student', 'exam'));
    }

    public function statistics(Request $request)
    {
        $query = Grade::whereNotNull('end_time');

        if ($request->exam_id) {
            $query->where('exam_id', $request->exam_id);
        }

        $grades = $query->get();

        return response()->json([
            'total' => $grades->count(),
            'average' => round($grades->avg('grade') ?? 0, 2),
            'highest' => $grades->max('grade') ?? 0,
            'lowest' => $grades->min('grade') ?? 0,
            'passed' => $grades->where('status', 'passed')->count(),
            'failed' => $grades->where('status', 'failed')->count(),
        ]);
    }
}
