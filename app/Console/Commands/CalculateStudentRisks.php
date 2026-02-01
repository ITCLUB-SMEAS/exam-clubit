<?php

namespace App\Console\Commands;

use App\Events\HighRiskStudentsDetected;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\StudentRiskPrediction;
use App\Services\PredictiveAnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculateStudentRisks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:calculate-risks
                            {--exam= : Calculate for specific exam ID}
                            {--hours=24 : Hours ahead to look for upcoming exams}
                            {--notify : Send notifications for high-risk students}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate risk predictions for students with upcoming exams';

    /**
     * Execute the console command.
     */
    public function handle(PredictiveAnalyticsService $service): int
    {
        $this->info('Starting student risk calculation...');

        $examId = $this->option('exam');
        $hours = (int) $this->option('hours');
        $shouldNotify = $this->option('notify');

        $totalPredictions = 0;
        $highRiskCount = 0;

        try {
            if ($examId) {
                // Calculate for specific exam
                $exam = Exam::findOrFail($examId);
                $predictions = $this->processExam($service, $exam);
                $totalPredictions = $predictions->count();
                $highRiskCount = $predictions->filter->isHighRisk()->count();
            } else {
                // Find all upcoming exam sessions
                $upcomingSessions = ExamSession::where('start_time', '>', now())
                    ->where('start_time', '<=', now()->addHours($hours))
                    ->with('exam')
                    ->get();

                if ($upcomingSessions->isEmpty()) {
                    $this->info("No upcoming exam sessions in the next {$hours} hours.");

                    return Command::SUCCESS;
                }

                $this->info("Found {$upcomingSessions->count()} upcoming exam sessions.");

                $progressBar = $this->output->createProgressBar($upcomingSessions->count());
                $progressBar->start();

                $processedExams = [];

                foreach ($upcomingSessions as $session) {
                    // Skip if exam already processed (multiple sessions for same exam)
                    if (in_array($session->exam_id, $processedExams)) {
                        $progressBar->advance();

                        continue;
                    }

                    if ($session->exam) {
                        $predictions = $this->processExam($service, $session->exam);
                        $totalPredictions += $predictions->count();
                        $highRiskCount += $predictions->filter->isHighRisk()->count();
                        $processedExams[] = $session->exam_id;
                    }

                    $progressBar->advance();
                }

                $progressBar->finish();
                $this->newLine();
            }

            // Output summary
            $this->newLine();
            $this->info('Calculation complete!');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Predictions', $totalPredictions],
                    ['High Risk Students', $highRiskCount],
                    ['Critical Risk Students', StudentRiskPrediction::recent(1)->critical()->count()],
                ]
            );

            // Dispatch notification event if there are high-risk students
            if ($shouldNotify && $highRiskCount > 0) {
                $this->info('Sending notifications for high-risk students...');

                $highRiskPredictions = StudentRiskPrediction::recent(1)
                    ->highRisk()
                    ->notNotified()
                    ->with(['student', 'exam'])
                    ->get();

                if ($highRiskPredictions->isNotEmpty()) {
                    HighRiskStudentsDetected::dispatch($highRiskPredictions);
                    $this->info("Notification dispatched for {$highRiskPredictions->count()} students.");
                }
            }

            Log::info('Student risk calculation completed', [
                'total_predictions' => $totalPredictions,
                'high_risk_count' => $highRiskCount,
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error calculating risks: {$e->getMessage()}");
            Log::error('Student risk calculation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Process predictions for a single exam
     */
    protected function processExam(PredictiveAnalyticsService $service, Exam $exam): \Illuminate\Support\Collection
    {
        $this->line("Processing: {$exam->title}");

        return $service->generatePredictionsForExam($exam);
    }
}
