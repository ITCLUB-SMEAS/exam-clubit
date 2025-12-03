<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOrGuru
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        if (!$user || (!$user->isAdmin() && !$user->isGuru())) {
            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }
}
