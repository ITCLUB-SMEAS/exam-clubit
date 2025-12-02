<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class CleanupExpiredTokens extends Command
{
    protected $signature = 'tokens:cleanup {--days=7 : Delete tokens older than X days}';
    protected $description = 'Cleanup expired Sanctum tokens';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        
        $deleted = PersonalAccessToken::where('created_at', '<', now()->subDays($days))
            ->orWhere(function ($query) {
                $query->whereNotNull('expires_at')
                      ->where('expires_at', '<', now());
            })
            ->delete();

        $this->info("Deleted {$deleted} expired tokens.");

        return Command::SUCCESS;
    }
}
