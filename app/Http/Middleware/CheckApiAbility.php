<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiAbility
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($role === 'admin' && !$user->isAdmin()) {
            return response()->json(['message' => 'Forbidden. Admin access required.'], 403);
        }

        if ($role === 'guru' && !$user->isGuru() && !$user->isAdmin()) {
            return response()->json(['message' => 'Forbidden. Guru or Admin access required.'], 403);
        }

        return $next($request);
    }
}
