<?php

namespace App\Jobs;

use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTelegramNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        public string $type,
        public array $data
    ) {}

    public function handle(TelegramService $telegram): void
    {
        match($this->type) {
            'violation' => $telegram->sendViolationAlert($this->data),
            'blocked' => $telegram->sendStudentBlockedAlert(
                \App\Models\Student::find($this->data['student_id']),
                $this->data['reason']
            ),
            'message' => $telegram->sendToAll($this->data['message']),
            default => null,
        };
    }
}
