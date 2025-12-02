<?php

use Illuminate\Support\Facades\Schedule;

// Cleanup expired tokens daily at midnight
Schedule::command('tokens:cleanup')->daily();

// Cleanup old activity logs (older than 90 days) weekly
Schedule::command('model:prune', ['--model' => \App\Models\ActivityLog::class])->weekly();
