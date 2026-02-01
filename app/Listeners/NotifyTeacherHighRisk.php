<?php

namespace App\Listeners;

use App\Events\HighRiskStudentsDetected;
use App\Mail\HighRiskStudentsMail;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyTeacherHighRisk implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(HighRiskStudentsDetected $event): void
    {
        $predictions = $event->predictions;

        if ($predictions->isEmpty()) {
            return;
        }

        // Group by exam to send targeted notifications
        $groupedByExam = $event->getGroupedByExam();

        foreach ($groupedByExam as $examId => $examPredictions) {
            $exam = $examPredictions->first()?->exam;

            if (! $exam) {
                continue;
            }

            // Get teachers to notify (admins/teachers)
            $recipients = $this->getNotificationRecipients();

            if ($recipients->isEmpty()) {
                Log::warning('No recipients found for high-risk notification', [
                    'exam_id' => $examId,
                ]);

                continue;
            }

            // Prepare data for notification
            $notificationData = [
                'exam' => $exam,
                'predictions' => $examPredictions,
                'critical_count' => $examPredictions->filter(fn ($p) => $p->isCritical())->count(),
                'high_count' => $examPredictions->count(),
            ];

            // Send email to each recipient
            foreach ($recipients as $recipient) {
                try {
                    Mail::to($recipient->email)
                        ->queue(new HighRiskStudentsMail($notificationData));

                    Log::info('High-risk notification sent', [
                        'recipient' => $recipient->email,
                        'exam_id' => $examId,
                        'student_count' => $examPredictions->count(),
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send high-risk notification', [
                        'recipient' => $recipient->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Mark predictions as notified
            foreach ($examPredictions as $prediction) {
                $prediction->markAsNotified();
            }

            // Log activity
            ActivityLogService::log(
                action: 'high_risk_notification_sent',
                module: 'predictive_analytics',
                description: "Notifikasi dikirim untuk {$examPredictions->count()} siswa berisiko di ujian {$exam->title}",
                metadata: [
                    'exam_id' => $examId,
                    'student_count' => $examPredictions->count(),
                    'critical_count' => $notificationData['critical_count'],
                ]
            );
        }
    }

    /**
     * Get users who should receive notifications
     */
    protected function getNotificationRecipients(): \Illuminate\Support\Collection
    {
        // Get admin users - adjust based on your user role system
        return User::where('email', '!=', null)
            ->whereNotNull('email_verified_at')
            ->limit(10)
            ->get();
    }

    /**
     * Handle a job failure.
     */
    public function failed(HighRiskStudentsDetected $event, \Throwable $exception): void
    {
        Log::error('High-risk notification listener failed', [
            'prediction_count' => $event->getCount(),
            'error' => $exception->getMessage(),
        ]);
    }
}
