<?php

namespace App\Http\Middleware;

use App\Services\TwoFactorService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorChallenge
{
    public function __construct(protected TwoFactorService $twoFactor) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || !$this->twoFactor->isEnabled($user)) {
            return $next($request);
        }

        // Check if 2FA already verified in this session
        if (session('2fa_verified') === $user->id) {
            return $next($request);
        }

        // Store intended URL
        session(['url.intended' => $request->url()]);

        return redirect()->route('admin.two-factor.challenge');
    }
}
