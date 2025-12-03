<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(20);
        
        return inertia('Admin/Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function unread()
    {
        $notifications = auth()->user()
            ->unreadNotifications()
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($n) => [
                'id' => $n->id,
                'data' => $n->data,
                'created_at' => $n->created_at->diffForHumans(),
            ]);

        $count = auth()->user()->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'count' => $count,
        ]);
    }

    public function markAsRead(Request $request)
    {
        if ($request->id) {
            auth()->user()->notifications()->where('id', $request->id)->update(['read_at' => now()]);
        } else {
            auth()->user()->unreadNotifications->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    public function destroy(string $id)
    {
        auth()->user()->notifications()->where('id', $id)->delete();
        
        return back()->with('success', 'Notifikasi dihapus.');
    }

    public function destroyAll()
    {
        auth()->user()->notifications()->delete();
        
        return back()->with('success', 'Semua notifikasi dihapus.');
    }
}
