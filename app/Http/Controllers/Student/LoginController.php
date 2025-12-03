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
        $request->validate([
            "nisn" => "required",
            "password" => "required|string",
        ]);

        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, $this->maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = ceil($seconds / 60);

            return redirect()
                ->back()
                ->with("error", "Terlalu banyak percobaan login. Silakan coba lagi dalam {$minutes} menit.")
                ->with("retry_after", $seconds)
                ->withInput($request->only("nisn"));
        }

        $student = Student::where("nisn", $request->nisn)->first();

        // Timing attack prevention: always perform hash check
        // Use a properly generated dummy hash if student not found
        $dummyHash = '$2y$12$K4o0hLJLfLKJfLKJfLKJfOK4o0hLJLfLKJfLKJfLKJfOK4o0hLJLf';
        $passwordToCheck = $student ? $student->password : $dummyHash;
        $validPassword = Hash::check($request->password, $passwordToCheck) && $student;

        if (!$validPassword) {
            RateLimiter::hit($throttleKey, $this->decaySeconds);
            $attemptsLeft = RateLimiter::remaining($throttleKey, $this->maxAttempts);

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

        // Check if student is blocked
        if ($student->is_blocked) {
            ActivityLogService::logLogin("student", $student, "blocked");
            return redirect()
                ->back()
                ->with("error", "Akun Anda telah diblokir karena pelanggaran. Hubungi admin untuk informasi lebih lanjut.")
                ->withInput($request->only("nisn"));
        }

        RateLimiter::clear($throttleKey);
        Auth::guard("student")->login($student);
        $request->session()->regenerate();
        $student->updateSessionInfo(session()->getId(), $request->ip());
        ActivityLogService::logLogin("student", $student, "success");

        return redirect()->route("student.dashboard");
    }

    /**
     * Generate throttle key for rate limiting
     */
    protected function throttleKey(Request $request): string
    {
        $nisn = Str::transliterate(Str::lower($request->input("nisn", "")));
        return "student_login:" . $nisn . "|" . $request->ip();
    }
}
