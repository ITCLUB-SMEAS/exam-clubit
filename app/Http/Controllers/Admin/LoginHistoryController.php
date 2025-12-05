<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use Illuminate\Http\Request;

class LoginHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = LoginHistory::query()->orderBy('created_at', 'desc');

        if ($request->user_type) {
            $query->where('user_type', $request->user_type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $histories = $query->paginate(20)->withQueryString();

        // Get stats
        $stats = [
            'total_today' => LoginHistory::whereDate('created_at', today())->count(),
            'success_today' => LoginHistory::whereDate('created_at', today())->where('status', 'success')->count(),
            'failed_today' => LoginHistory::whereDate('created_at', today())->where('status', 'failed')->count(),
        ];

        return inertia('Admin/LoginHistory/Index', [
            'histories' => $histories,
            'filters' => $request->only(['user_type', 'status']),
            'stats' => $stats,
        ]);
    }
}
