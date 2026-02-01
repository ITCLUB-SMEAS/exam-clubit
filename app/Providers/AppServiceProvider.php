<?php

namespace App\Providers;

use App\Models\Exam;
use App\Policies\ExamPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Exam::class, ExamPolicy::class);

        // Rate limiters for exam operations (scaled for 500 concurrent students)
        RateLimiter::for('exam', fn (Request $request) => Limit::perMinute(200)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('anticheat', fn (Request $request) => Limit::perMinute(100)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('heartbeat', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        // API rate limits from config
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute((int) config('security.rate_limits.api.general', 60))
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('api-write', fn (Request $request) => Limit::perMinute((int) config('security.rate_limits.api.write', 30))
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('api-sensitive', fn (Request $request) => Limit::perMinute((int) config('security.rate_limits.api.sensitive', 10))
            ->by($request->user()?->id ?: $request->ip()));

        // Login rate limit from config
        RateLimiter::for('login', fn (Request $request) => Limit::perMinutes(
            (int) config('security.rate_limits.login.decay_minutes', 5),
            (int) config('security.rate_limits.login.max_attempts', 5)
        )->by($request->ip()));

        // Profile/account operations - prevent enumeration and abuse
        RateLimiter::for('profile', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()));

        // File uploads - stricter limit
        RateLimiter::for('upload', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));

        // Import operations - very strict
        RateLimiter::for('import', fn (Request $request) => Limit::perMinute(3)->by($request->user()?->id ?: $request->ip()));

        // Export/download operations
        RateLimiter::for('export', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));
    }
}
