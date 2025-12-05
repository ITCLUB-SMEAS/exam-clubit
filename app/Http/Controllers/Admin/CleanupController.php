<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ExamViolation;
use App\Models\LoginHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class CleanupController extends Controller
{
    public function index()
    {
        return inertia('Admin/Cleanup/Index', [
            'stats' => $this->getStats(),
        ]);
    }

    public function cleanup(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:7|max:365',
        ]);

        Artisan::call('cleanup:old-data', ['--days' => $request->days]);
        
        return back()->with('success', 'Cleanup berhasil! Data lama telah dihapus.');
    }

    protected function getStats(): array
    {
        $days90 = now()->subDays(90);
        
        return [
            'activity_logs' => [
                'total' => ActivityLog::count(),
                'old' => ActivityLog::where('created_at', '<', $days90)->count(),
            ],
            'login_history' => [
                'total' => LoginHistory::count(),
                'old' => LoginHistory::where('created_at', '<', $days90)->count(),
            ],
            'violations' => [
                'total' => ExamViolation::count(),
                'old' => ExamViolation::where('created_at', '<', $days90)->count(),
            ],
            'backups' => [
                'total' => count(Storage::disk('local')->files('backups')),
            ],
        ];
    }
}
