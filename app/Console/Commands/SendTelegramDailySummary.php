<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class SendTelegramDailySummary extends Command
{
    protected $signature = 'telegram:daily-summary';
    protected $description = 'Send daily summary to Telegram';

    public function handle(TelegramService $telegram)
    {
        $telegram->sendDailySummary();
        $this->info('Daily summary sent to Telegram.');
    }
}
