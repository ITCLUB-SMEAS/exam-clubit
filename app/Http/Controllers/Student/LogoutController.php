<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    /**
     * Handle the student logout request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Request $request)
    {
        // Get the current authenticated student
        /** @var Student|null $student */
        $student = Auth::guard("student")->user();

        // Log logout activity before clearing session
        if ($student) {
            ActivityLogService::logLogout("student", $student);
            $student->clearSessionInfo();
        }

        // Logout the student from the guard
        Auth::guard("student")->logout();

        // Invalidate the current session
        $request->session()->invalidate();

        // Regenerate CSRF token
        $request->session()->regenerateToken();

        // Redirect to login page with success message
        return redirect("/")->with("success", "Anda berhasil logout.");
    }
}
