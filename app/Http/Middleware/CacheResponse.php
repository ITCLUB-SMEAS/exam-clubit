<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheResponse
{
    public function handle(Request $request, Closure $next, int $minutes = 60): Response
    {
        if ($request->method() !== 'GET' || $request->user()) {
            return $next($request);
        }

        $key = 'response_cache:' . md5($request->fullUrl());

        return Cache::remember($key, $minutes * 60, fn() => $next($request));
    }
}
