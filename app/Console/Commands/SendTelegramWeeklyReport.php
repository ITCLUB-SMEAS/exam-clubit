<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class SendTelegramWeeklyReport extends Command
{
    protected $signature = 'telegram:weekly-report';
    protected $description = 'Send weekly report to Telegram';

    public function handle(TelegramService $telegram)
    {
        $telegram->sendWeeklyReport();
        $this->info('Weekly report sent to Telegram.');
    }
}
