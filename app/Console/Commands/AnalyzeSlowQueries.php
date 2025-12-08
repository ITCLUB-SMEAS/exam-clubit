<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeSlowQueries extends Command
{
    protected $signature = 'db:analyze-slow-queries';
    protected $description = 'Analyze and report slow database queries';

    public function handle()
    {
        $this->info('Analyzing database queries...');

        // Enable query logging
        DB::enableQueryLog();

        // Run common queries
        $this->testCommonQueries();

        // Get logged queries
        $queries = DB::getQueryLog();

        // Analyze slow queries (> 100ms)
        $slowQueries = collect($queries)->filter(fn($q) => $q['time'] > 100);

        if ($slowQueries->isEmpty()) {
            $this->info('✓ No slow queries detected!');
            return 0;
        }

        $this->warn("Found {$slowQueries->count()} slow queries:");
        
        foreach ($slowQueries as $query) {
            $this->line("Time: {$query['time']}ms");
            $this->line("Query: " . substr($query['query'], 0, 100) . "...");
            $this->line('---');
        }

        return 0;
    }

    private function testCommonQueries()
    {
        // Test common queries
        \App\Models\Grade::with('student', 'exam')->limit(10)->get();
        \App\Models\Answer::with('question', 'student')->limit(10)->get();
        \App\Models\ExamSession::with('exam.lesson')->limit(10)->get();
    }
}
