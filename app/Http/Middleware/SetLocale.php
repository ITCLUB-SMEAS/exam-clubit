<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->getLocale($request);
        
        if ($this->isValidLocale($locale)) {
            App::setLocale($locale);
            Session::put('locale', $locale);
        }

        return $next($request);
    }

    protected function getLocale(Request $request): string
    {
        // 1. Check URL parameter
        if ($request->has('lang')) {
            return $request->get('lang');
        }

        // 2. Check session
        if (Session::has('locale')) {
            return Session::get('locale');
        }

        // 3. Check user preference (if authenticated)
        if ($request->user()) {
            return $request->user()->locale ?? config('languages.default');
        }

        // 4. Check student preference
        if (auth()->guard('student')->check()) {
            return auth()->guard('student')->user()->locale ?? config('languages.default');
        }

        // 5. Default
        return config('languages.default');
    }

    protected function isValidLocale(string $locale): bool
    {
        return array_key_exists($locale, config('languages.available'));
    }
}
