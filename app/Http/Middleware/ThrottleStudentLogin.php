<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ThrottleStudentLogin
{
    /**
     * Maximum number of login attempts allowed
     */
    protected int $maxAttempts = 5;

    /**
     * Decay time in seconds (lockout duration)
     */
    protected int $decaySeconds = 300; // 5 minutes

    /**
     * Handle an incoming request.
     *
     * Rate limiting untuk login student:
     * - Maksimal 5 percobaan login dalam 5 menit
     * - Setelah 5x gagal, harus menunggu 5 menit
     * - Key berdasarkan kombinasi NISN + IP Address
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate throttle key based on NISN and IP
        $key = $this->throttleKey($request);

        // Check if too many attempts
        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            return $this->buildTooManyAttemptsResponse($key, $request);
        }

        // Process the request
        $response = $next($request);

        // If login failed (redirect back means failed), increment attempts
        if ($this->isFailedLoginResponse($response, $request)) {
            RateLimiter::hit($key, $this->decaySeconds);
        } else {
            // Login successful, clear rate limiter
            RateLimiter::clear($key);
        }

        return $response;
    }

    /**
     * Generate the throttle key for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function throttleKey(Request $request): string
    {
        $nisn = Str::transliterate(Str::lower($request->input('nisn', '')));
        $ip = $request->ip();

        return 'student_login:' . $nisn . '|' . $ip;
    }

    /**
     * Build the response for too many attempts.
     *
     * @param  string  $key
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function buildTooManyAttemptsResponse(string $key, Request $request): Response
    {
        $seconds = RateLimiter::availableIn($key);
        $minutes = ceil($seconds / 60);

        $message = "Terlalu banyak percobaan login. Silakan coba lagi dalam {$minutes} menit.";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'retry_after' => $seconds,
            ], 429);
        }

        return redirect('/')
            ->with('error', $message)
            ->with('retry_after', $seconds);
    }

    /**
     * Determine if the response indicates a failed login attempt.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function isFailedLoginResponse(Response $response, Request $request): bool
    {
        // Check if response is a redirect back (indicates failed login)
        if ($response->isRedirection()) {
            $targetUrl = $response->headers->get('Location');
            $currentUrl = $request->url();

            // If redirecting back to the same page or root, it's likely a failed login
            if ($targetUrl === $currentUrl || $targetUrl === url('/')) {
                // Check if there's an error in session
                return session()->has('error') || session()->has('errors');
            }
        }

        return false;
    }

    /**
     * Get the number of remaining attempts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int
     */
    public function remainingAttempts(Request $request): int
    {
        $key = $this->throttleKey($request);
        return RateLimiter::remaining($key, $this->maxAttempts);
    }

    /**
     * Get the number of seconds until the next retry is allowed.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int
     */
    public function availableIn(Request $request): int
    {
        $key = $this->throttleKey($request);
        return RateLimiter::availableIn($key);
    }
}
