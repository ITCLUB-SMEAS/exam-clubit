<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventDebugInProduction
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production') && config('app.debug')) {
            // Log critical security issue
            \Log::critical('DEBUG MODE ENABLED IN PRODUCTION!', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            // Force disable debug
            config(['app.debug' => false]);
        }

        return $next($request);
    }
}
