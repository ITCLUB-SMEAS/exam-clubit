<?php

namespace App\Http\Controllers\Student;

use App\Models\Student;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    /**
     * Maximum login attempts before lockout
     */
    protected int $maxAttempts = 5;

    /**
     * Lockout duration in seconds (5 minutes)
     */
    protected int $decaySeconds = 300;

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Request $request)
    {
        // Validate the form data
        $request->validate([
            "nisn" => "required|string",
            "password" => "required|string",
        ]);

        // Check rate limiting
        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, $this->maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = ceil($seconds / 60);

            return redirect()
                ->back()
                ->with(
                    "error",
                    "Terlalu banyak percobaan login. Silakan coba lagi dalam {$minutes} menit.",
                )
                ->with("retry_after", $seconds)
                ->withInput($request->only("nisn"));
        }

        // Find student by NISN
        $student = Student::where("nisn", $request->nisn)->first();

        // Check if student exists and password is correct
        if (!$student || !Hash::check($request->password, $student->password)) {
            // Increment failed attempts
            RateLimiter::hit($throttleKey, $this->decaySeconds);

            $attemptsLeft = RateLimiter::remaining(
                $throttleKey,
                $this->maxAttempts,
            );

            // Log failed login attempt
            if ($student) {
                ActivityLogService::logLogin("student", $student, "failed");
            }

            $errorMessage = "NISN atau Password salah.";
            if ($attemptsLeft > 0 && $attemptsLeft <= 3) {
                $errorMessage .= " Sisa percobaan: {$attemptsLeft}x.";
            }

            return redirect()
                ->back()
                ->with("error", $errorMessage)
                ->with("attempts_left", $attemptsLeft)
                ->withInput($request->only("nisn"));
        }

        // Check if student is already logged in from another device (optional: force logout)
        if ($student->hasActiveSession()) {
            // Option 1: Reject new login (uncomment to use)
            // return redirect()->back()
            //     ->with('error', 'Akun ini sedang digunakan di perangkat lain. Silakan logout terlebih dahulu.')
            //     ->withInput($request->only('nisn'));

            // Option 2: Force logout previous session (current implementation)
            // The previous session will be invalidated when they try to access
            // because the session_id won't match
        }

        // Clear rate limiter on successful login
        RateLimiter::clear($throttleKey);

        // Login the student
        Auth::guard("student")->login($student);

        // Regenerate session to prevent session fixation attacks
        $request->session()->regenerate();

        // Update student's session information
        $student->updateSessionInfo(session()->getId(), $request->ip());

        // Log successful login
        ActivityLogService::logLogin("student", $student, "success");

        // Redirect to dashboard
        return redirect()->route("student.dashboard");
    }

    /**
     * Generate throttle key for rate limiting
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function throttleKey(Request $request): string
    {
        $nisn = Str::transliterate(Str::lower($request->input("nisn", "")));
        return "student_login:" . $nisn . "|" . $request->ip();
    }
}
