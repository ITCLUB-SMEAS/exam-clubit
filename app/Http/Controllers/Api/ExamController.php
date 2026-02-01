<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExamResource;
use App\Http\Resources\ExamSessionResource;
use App\Models\Exam;
use App\Models\ExamSession;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index(Request $request)
    {
        $exams = Exam::with('lesson', 'classroom')
            ->withCount('questions')
            ->when($request->lesson_id, fn ($q) => $q->where('lesson_id', $request->lesson_id))
            ->when($request->classroom_id, fn ($q) => $q->where('classroom_id', $request->classroom_id))
            ->paginate($request->per_page ?? 15);

        return ExamResource::collection($exams);
    }

    public function show(Exam $exam)
    {
        $user = request()->user();

        // Non-admin: hide questions with answers
        if (! $user->isAdmin()) {
            $exam->load('lesson', 'classroom');

            return new ExamResource($exam);
        }

        return new ExamResource($exam->load('lesson', 'classroom', 'questions'));
    }

    public function sessions(Request $request)
    {
        $sessions = ExamSession::with('exam.lesson')
            ->when($request->active, function ($q) {
                $q->where('start_time', '<=', now())
                    ->where('end_time', '>=', now());
            })
            ->paginate($request->per_page ?? 15);

        return ExamSessionResource::collection($sessions);
    }
}
