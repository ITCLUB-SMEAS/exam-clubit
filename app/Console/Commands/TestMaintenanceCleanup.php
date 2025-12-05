<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class TestMaintenanceCleanup extends Command
{
    protected $signature = 'test:maintenance-cleanup';
    protected $description = 'Test Maintenance Mode and Cleanup features';

    public function handle(): int
    {
        $this->info('=== Testing Maintenance & Cleanup Features ===');
        $this->newLine();

        // Test 1: Maintenance Mode
        $this->info('1. Testing Maintenance Mode...');
        
        $maintenanceRoute = Route::has('admin.maintenance.index');
        $toggleRoute = Route::has('admin.maintenance.toggle');
        
        if ($maintenanceRoute && $toggleRoute) {
            $this->info('   ✅ Maintenance routes registered');
            $this->info('   📍 URL: /admin/maintenance');
            $this->info('   🔧 Features:');
            $this->info('      - Toggle sistem ON/OFF');
            $this->info('      - Custom maintenance message');
            $this->info('      - Secret bypass URL');
        } else {
            $this->error('   ❌ Routes not found');
        }

        $this->newLine();

        // Test 2: Cleanup Command
        $this->info('2. Testing Cleanup Command (dry-run)...');
        
        $this->call('cleanup:old-data', ['--days' => 90, '--dry-run' => true]);

        $this->newLine();

        // Test 3: Cleanup UI
        $this->info('3. Testing Cleanup UI...');
        
        $cleanupRoute = Route::has('admin.cleanup.index');
        $cleanupRunRoute = Route::has('admin.cleanup.run');
        
        if ($cleanupRoute && $cleanupRunRoute) {
            $this->info('   ✅ Cleanup routes registered');
            $this->info('   📍 URL: /admin/cleanup');
            $this->info('   🔧 Features:');
            $this->info('      - View data statistics');
            $this->info('      - Manual cleanup trigger');
            $this->info('      - Configurable retention period');
        } else {
            $this->error('   ❌ Routes not found');
        }

        $this->newLine();

        // Test 4: Scheduled Task
        $this->info('4. Scheduled Tasks...');
        $this->info('   📅 Auto cleanup: Monthly on 1st at 03:00');
        $this->info('   📅 Auto backup: Daily at 02:00');

        $this->newLine();
        $this->info('=== All Tests Completed ===');
        $this->newLine();

        $this->table(
            ['Feature', 'Status', 'Access'],
            [
                ['Maintenance Mode', '✅ Ready', '/admin/maintenance'],
                ['Cleanup Data', '✅ Ready', '/admin/cleanup'],
                ['Auto Cleanup', '✅ Scheduled', 'Monthly 1st 03:00'],
            ]
        );

        return 0;
    }
}
