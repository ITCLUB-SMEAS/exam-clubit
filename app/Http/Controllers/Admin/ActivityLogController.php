<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs.
     *
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $logs = ActivityLog::query()
            ->when($request->action, function ($q) use ($request) {
                $q->where("action", $request->action);
            })
            ->when($request->module, function ($q) use ($request) {
                $q->where("module", $request->module);
            })
            ->when($request->user_type, function ($q) use ($request) {
                $q->where("user_type", $request->user_type);
            })
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query
                        ->where("user_name", "like", "%{$request->search}%")
                        ->orWhere("description", "like", "%{$request->search}%")
                        ->orWhere("ip_address", "like", "%{$request->search}%");
                });
            })
            ->when($request->date_from, function ($q) use ($request) {
                $q->whereDate("created_at", ">=", $request->date_from);
            })
            ->when($request->date_to, function ($q) use ($request) {
                $q->whereDate("created_at", "<=", $request->date_to);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Get unique values for filters
        $actions = ActivityLog::query()
            ->distinct()
            ->pluck("action")
            ->filter()
            ->values();
        $modules = ActivityLog::query()
            ->distinct()
            ->pluck("module")
            ->filter()
            ->values();

        return inertia("Admin/ActivityLogs/Index", [
            "logs" => $logs,
            "actions" => $actions,
            "modules" => $modules,
            "filters" => $request->only([
                "action",
                "module",
                "user_type",
                "search",
                "date_from",
                "date_to",
            ]),
        ]);
    }

    /**
     * Display the specified activity log.
     *
     * @param  ActivityLog  $activityLog
     * @return Response
     */
    public function show(ActivityLog $activityLog): Response
    {
        return inertia("Admin/ActivityLogs/Show", [
            "log" => $activityLog,
        ]);
    }

    /**
     * Get activity stats for dashboard.
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        $stats = [
            "today_logins" => ActivityLog::query()
                ->where("action", "login")
                ->whereDate("created_at", today())
                ->count(),
            "today_failed_logins" => ActivityLog::query()
                ->where("action", "login_failed")
                ->whereDate("created_at", today())
                ->count(),
            "today_exams_started" => ActivityLog::query()
                ->where("action", "exam_start")
                ->whereDate("created_at", today())
                ->count(),
            "today_exams_completed" => ActivityLog::query()
                ->where("action", "exam_end")
                ->whereDate("created_at", today())
                ->count(),
            "total_activities_today" => ActivityLog::query()
                ->whereDate("created_at", today())
                ->count(),
            "recent_activities" => ActivityLog::query()
                ->latest()
                ->take(10)
                ->get()
                ->map(function ($log) {
                    return [
                        "id" => $log->id,
                        "user_name" => $log->user_name,
                        "user_type" => $log->user_type,
                        "action" => $log->action,
                        "description" => $log->description,
                        "ip_address" => $log->ip_address,
                        "created_at" => $log->created_at->diffForHumans(),
                    ];
                }),
        ];

        return response()->json($stats);
    }

    /**
     * Export activity logs to CSV.
     *
     * @param  Request  $request
     * @return StreamedResponse
     */
    public function export(Request $request): StreamedResponse
    {
        $logs = ActivityLog::query()
            ->when($request->action, function ($q) use ($request) {
                $q->where("action", $request->action);
            })
            ->when($request->module, function ($q) use ($request) {
                $q->where("module", $request->module);
            })
            ->when($request->user_type, function ($q) use ($request) {
                $q->where("user_type", $request->user_type);
            })
            ->when($request->date_from, function ($q) use ($request) {
                $q->whereDate("created_at", ">=", $request->date_from);
            })
            ->when($request->date_to, function ($q) use ($request) {
                $q->whereDate("created_at", "<=", $request->date_to);
            })
            ->latest()
            ->get();

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" =>
                'attachment; filename="activity_logs_' .
                date("Y-m-d_H-i-s") .
                '.csv"',
        ];

        $callback = function () use ($logs) {
            $file = fopen("php://output", "w");

            // Header row
            fputcsv($file, [
                "ID",
                "User Type",
                "User ID",
                "User Name",
                "Action",
                "Module",
                "Description",
                "IP Address",
                "URL",
                "Method",
                "Created At",
            ]);

            // Data rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->user_type,
                    $log->user_id,
                    $log->user_name,
                    $log->action,
                    $log->module,
                    $log->description,
                    $log->ip_address,
                    $log->url,
                    $log->method,
                    $log->created_at->format("Y-m-d H:i:s"),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Delete old activity logs.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function cleanup(Request $request): RedirectResponse
    {
        $request->validate([
            "days" => "required|integer|min:30",
        ]);

        $date = now()->subDays($request->days);
        $count = ActivityLog::query()->where("created_at", "<", $date)->count();

        ActivityLog::query()->where("created_at", "<", $date)->delete();

        return redirect()
            ->back()
            ->with(
                "success",
                "Berhasil menghapus {$count} log aktivitas yang lebih dari {$request->days} hari.",
            );
    }
}
