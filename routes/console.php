<?php

use Illuminate\Support\Facades\Schedule;

// Cleanup expired tokens daily at midnight
Schedule::command('tokens:cleanup')->daily();

// Cleanup old activity logs (older than 90 days) weekly
Schedule::command('model:prune', ['--model' => \App\Models\ActivityLog::class])->weekly();

// Telegram: Daily summary at 21:00
Schedule::command('telegram:daily-summary')->dailyAt('21:00');

// Telegram: Weekly report every Monday at 08:00
Schedule::command('telegram:weekly-report')->weeklyOn(1, '08:00');

// Server health check every 5 minutes
Schedule::command('server:health-check')->everyFiveMinutes();

// Exam starting alert - check every minute
Schedule::command('telegram:exam-starting-alert')->everyMinute();

// Backup reminder daily at 22:00
Schedule::command('telegram:backup-reminder')->dailyAt('22:00');

// Automated database backup daily at 02:00 with verification
Schedule::command('backup:automated --verify')->dailyAt('02:00');

// Auto cleanup old data (90 days) monthly on 1st at 03:00
Schedule::command('cleanup:old-data --days=90')->monthlyOn(1, '03:00');

// Performance report every 6 hours
Schedule::command('performance:report')->everySixHours();

// Cache warmup every hour
Schedule::command('cache:warmup')->hourly();

// Database optimization weekly on Sunday at 03:00
Schedule::command('db:optimize')->weeklyOn(0, '03:00');

// Calculate student risk predictions daily at 18:00 (evening before exam day)
// This runs H-1 to give teachers time for intervention
Schedule::command('students:calculate-risks --notify')->dailyAt('18:00');
