<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Services\ItemAnalysisService;

class ItemAnalysisController extends Controller
{
    public function __construct(protected ItemAnalysisService $analysisService)
    {}

    public function show(Exam $exam)
    {
        $analysis = $this->analysisService->analyzeExam($exam);

        return inertia('Admin/ItemAnalysis/Show', [
            'exam' => $exam->load('lesson', 'classroom'),
            'analysis' => $analysis['questions'],
            'summary' => $analysis['summary'],
            'total_students' => $analysis['total_students'] ?? 0,
        ]);
    }
}
