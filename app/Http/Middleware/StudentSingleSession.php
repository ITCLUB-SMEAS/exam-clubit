<?php

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StudentSingleSession
{
    /**
     * Handle an incoming request.
     *
     * Middleware ini memastikan bahwa student hanya bisa login di satu device/browser
     * Jika student login di tempat lain, session sebelumnya akan di-invalidate
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

        if ($student) {
            $currentSessionId = session()->getId();

            // Check if the stored session_id matches the current session
            if (
                $student->session_id &&
                !$student->isCurrentSession($currentSessionId)
            ) {
                // Session doesn't match - this means student logged in from another device
                // Logout the current session
                Auth::guard("student")->logout();

                // Invalidate the session
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Redirect to login with error message
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

            // If session_id is null but user is authenticated, update it
            // This handles the case for existing students who logged in before this feature
            if (!$student->session_id) {
                $student->updateSessionInfo($currentSessionId, $request->ip());
            }
        }

        return $next($request);
    }
}
