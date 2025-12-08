<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegenerateSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Regenerate session after login/privilege change
        if ($this->shouldRegenerateSession($request)) {
            $request->session()->regenerate();
        }

        return $response;
    }

    protected function shouldRegenerateSession(Request $request): bool
    {
        // Check if just logged in
        if ($request->session()->has('just_logged_in')) {
            $request->session()->forget('just_logged_in');
            return true;
        }

        return false;
    }
}
