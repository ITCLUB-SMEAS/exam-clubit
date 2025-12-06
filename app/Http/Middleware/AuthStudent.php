<?php

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthStudent
{
    /**
     * Handle an incoming request.
     *
     * Middleware ini melakukan:
     * 1. Mengecek apakah student sudah login
     * 2. Memvalidasi session untuk mencegah multi-login
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if student is authenticated
        /** @var Student|null $student */
        $student = Auth::guard("student")->user();

        // If not authenticated, redirect to login page
        if (!$student) {
            if ($request->expectsJson()) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Silakan login terlebih dahulu.",
                    ],
                    401,
                );
            }

            return redirect("/")->with(
                "error",
                "Silakan login terlebih dahulu.",
            );
        }

        // Validate session - prevent multi-login
        $currentSessionId = session()->getId();

        // Check if session_id exists and doesn't match current session
        if (
            $student->session_id &&
            !$student->isCurrentSession($currentSessionId)
        ) {
            // Force logout - session has been taken over by another device
            Auth::guard("student")->logout();

            // Invalidate the session
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "Sesi Anda telah berakhir karena login dari perangkat lain.",
                    ],
                    401,
                );
            }

            return redirect("/")->with(
                "error",
                "Sesi Anda telah berakhir karena login dari perangkat lain. Silakan login kembali.",
            );
        }

        // Check if student is blocked
        if ($student->is_blocked) {
            Auth::guard("student")->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    "success" => false,
                    "message" => "Akun Anda telah diblokir. Hubungi admin.",
                    "is_blocked" => true,
                ], 403);
            }

            return redirect("/")->with("error", "Akun Anda telah diblokir: " . ($student->block_reason ?? "Hubungi admin untuk informasi lebih lanjut."));
        }

        // If student has no session_id stored (legacy data), update it
        if (!$student->session_id) {
            $student->updateSessionInfo($currentSessionId, $request->ip());
        }

        // Continue to the next middleware/request
        return $next($request);
    }
}
