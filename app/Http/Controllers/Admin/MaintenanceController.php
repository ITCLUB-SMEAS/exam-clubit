<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class MaintenanceController extends Controller
{
    public function index()
    {
        return inertia('Admin/Maintenance/Index', [
            'isDown' => app()->isDownForMaintenance(),
            'settings' => $this->getSettings(),
        ]);
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'message' => 'nullable|string|max:500',
            'secret' => 'nullable|string|max:50',
            'allowed_ips' => 'nullable|string',
        ]);

        if (app()->isDownForMaintenance()) {
            // Bring up
            Artisan::call('up');
            Cache::forget('maintenance_settings');
            return back()->with('success', 'Sistem berhasil diaktifkan kembali.');
        } else {
            // Bring down
            $settings = [
                'message' => $request->message ?? 'Sistem sedang dalam pemeliharaan.',
                'secret' => $request->secret ?? null,
                'allowed_ips' => $request->allowed_ips ?? null,
            ];
            
            Cache::put('maintenance_settings', $settings, now()->addDays(7));

            $command = 'down';
            if ($settings['secret']) {
                $command .= ' --secret="' . $settings['secret'] . '"';
            }

            Artisan::call($command);
            
            return back()->with('success', 'Sistem berhasil dinonaktifkan untuk pemeliharaan.');
        }
    }

    protected function getSettings(): array
    {
        return Cache::get('maintenance_settings', [
            'message' => 'Sistem sedang dalam pemeliharaan.',
            'secret' => '',
            'allowed_ips' => '',
        ]);
    }
}
