<?php

namespace App\Jobs;

use App\Models\Exam;
use App\Models\Grade;
use App\Services\TelegramService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExportPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(
        public int $examId,
        public string $chatId
    ) {}

    public function handle(TelegramService $telegram): void
    {
        $exam = Exam::find($this->examId);
        if (!$exam) return;

        $grades = Grade::where('exam_id', $this->examId)
            ->whereNotNull('end_time')
            ->with(['student.classroom'])
            ->orderByDesc('grade')
            ->get();

        if ($grades->isEmpty()) {
            $telegram->sendMessage("⚠️ Belum ada hasil ujian untuk: {$exam->title}", $this->chatId);
            return;
        }

        $stats = [
            'total' => $grades->count(),
            'average' => round($grades->avg('grade') ?? 0, 1),
            'highest' => $grades->max('grade') ?? 0,
            'lowest' => $grades->min('grade') ?? 0,
            'passed' => $grades->where('status', 'passed')->count(),
            'failed' => $grades->where('status', 'failed')->count(),
        ];

        $pdf = Pdf::loadView('exports.exam-results', compact('exam', 'grades', 'stats'));
        $pdf->setPaper('a4', 'landscape');

        $filename = "hasil_ujian_{$exam->id}.pdf";
        $tempPath = storage_path("app/temp/{$filename}");

        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $pdf->save($tempPath);

        $caption = "📊 <b>Hasil Ujian</b>\n{$exam->title}\nTotal: {$stats['total']} siswa | Rata-rata: {$stats['average']}";
        $telegram->sendDocument($tempPath, $filename, $this->chatId, $caption);

        @unlink($tempPath);
    }
}
