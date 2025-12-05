<?php

namespace App\Console\Commands;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\LoginHistory;
use Illuminate\Console\Command;

class TestNewFeatures extends Command
{
    protected $signature = 'test:new-features';
    protected $description = 'Test Schedule Conflict Detection and Login History';

    public function handle(): int
    {
        $this->info('=== Testing New Features ===');
        $this->newLine();

        // Test 1: Login History
        $this->info('1. Testing Login History...');
        
        $history = LoginHistory::create([
            'user_type' => 'admin',
            'user_id' => 1,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'device' => 'Desktop',
            'browser' => 'Chrome',
            'platform' => 'Windows',
            'status' => 'success',
            'created_at' => now(),
        ]);

        if ($history->id) {
            $this->info("   ✅ Login History recorded: ID #{$history->id}");
            $this->info("      - Device: {$history->device}");
            $this->info("      - Browser: {$history->browser}");
            $this->info("      - Platform: {$history->platform}");
        } else {
            $this->error('   ❌ Failed to record login history');
        }

        $this->newLine();

        // Test 2: Schedule Conflict Detection
        $this->info('2. Testing Schedule Conflict Detection...');

        $exam = Exam::first();
        if (!$exam) {
            $this->warn('   ⚠️ No exam found, skipping conflict test');
            return 0;
        }

        // Create a test session
        $session1 = ExamSession::create([
            'title' => 'Test Session 1',
            'exam_id' => $exam->id,
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(2),
        ]);
        $this->info("   Created test session: {$session1->title}");
        $this->info("   Time: {$session1->start_time->format('H:i')} - {$session1->end_time->format('H:i')}");

        // Check for conflicts
        $conflicts = ExamSession::whereHas('exam', fn($q) => $q->where('classroom_id', $exam->classroom_id))
            ->where('id', '!=', $session1->id)
            ->where(function ($q) use ($session1) {
                $q->whereBetween('start_time', [$session1->start_time, $session1->end_time])
                  ->orWhereBetween('end_time', [$session1->start_time, $session1->end_time])
                  ->orWhere(function ($q2) use ($session1) {
                      $q2->where('start_time', '<=', $session1->start_time)
                         ->where('end_time', '>=', $session1->end_time);
                  });
            })
            ->get();

        if ($conflicts->isEmpty()) {
            $this->info('   ✅ No conflicts detected (as expected for new session)');
        } else {
            $this->info('   ⚠️ Conflicts found: ' . $conflicts->pluck('title')->join(', '));
        }

        // Cleanup test data
        $session1->delete();
        $history->delete();
        $this->info('   🧹 Test data cleaned up');

        $this->newLine();
        $this->info('=== All Tests Completed ===');

        return 0;
    }
}
