<?php

namespace App\Providers;

use App\Models\Exam;
use App\Policies\ExamPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Exam::class, ExamPolicy::class);

        // Rate limiters for 500 concurrent students
        RateLimiter::for('exam', fn(Request $request) => 
            Limit::perMinute(200)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('anticheat', fn(Request $request) => 
            Limit::perMinute(100)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('heartbeat', fn(Request $request) => 
            Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('api', fn(Request $request) => 
            Limit::perMinute(150)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('login', fn(Request $request) => 
            Limit::perMinute(5)->by($request->ip()));
    }
}
