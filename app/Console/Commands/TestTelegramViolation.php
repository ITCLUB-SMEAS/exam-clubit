<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestTelegramViolation extends Command
{
    protected $signature = 'test:telegram-violation';
    protected $description = 'Test Telegram violation alert with photo';

    public function handle(TelegramService $telegram): int
    {
        $this->info('Testing Telegram Violation Alert...');

        // Create dummy test image
        $testImagePath = storage_path('app/test_violation.jpg');
        $this->createTestImage($testImagePath);

        $data = [
            'student_name' => 'Test Student',
            'student_nisn' => '1234567890',
            'exam_title' => 'UTS Matematika Kelas 12',
            'violation_type' => 'Pindah Tab/Window',
            'description' => 'Siswa berpindah ke tab lain (TEST)',
            'violation_count' => 2,
            'ip_address' => '192.168.1.100',
        ];

        $this->info('Sending violation alert with photo...');
        
        $result = $telegram->sendViolationAlert($data, $testImagePath);

        // Cleanup test image
        @unlink($testImagePath);

        if ($result) {
            $this->info('✅ Telegram notification sent successfully!');
            return 0;
        } else {
            $this->error('❌ Failed to send Telegram notification.');
            $this->warn('Check your TELEGRAM_BOT_TOKEN and TELEGRAM_NOTIFY_IDS in .env');
            return 1;
        }
    }

    protected function createTestImage(string $path): void
    {
        // Create simple test image with GD
        $img = imagecreatetruecolor(320, 240);
        $bg = imagecolorallocate($img, 50, 50, 50);
        $text = imagecolorallocate($img, 255, 255, 255);
        $red = imagecolorallocate($img, 255, 100, 100);
        
        imagefill($img, 0, 0, $bg);
        imagestring($img, 5, 80, 100, 'TEST VIOLATION', $text);
        imagestring($img, 3, 90, 130, date('Y-m-d H:i:s'), $red);
        
        imagejpeg($img, $path, 80);
        imagedestroy($img);
    }
}
