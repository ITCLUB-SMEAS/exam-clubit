<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ValidateTurnstile
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->input('cf_turnstile_response');

        if (!$token) {
            return back()->withErrors(['cf_turnstile_response' => 'Verifikasi Turnstile diperlukan.']);
        }

        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => config('services.turnstile.secret'),
            'response' => $token,
            'remoteip' => $request->ip(),
        ]);

        if (!$response->json('success')) {
            return back()->withErrors(['cf_turnstile_response' => 'Verifikasi Turnstile gagal.']);
        }

        return $next($request);
    }
}
