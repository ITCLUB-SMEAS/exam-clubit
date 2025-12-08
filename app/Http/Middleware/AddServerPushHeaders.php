<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddServerPushHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->secure() && method_exists($response, 'header')) {
            $links = [
                '</build/assets/app.css>; rel=preload; as=style',
                '</build/assets/app.js>; rel=preload; as=script',
            ];
            
            $response->header('Link', implode(', ', $links));
        }

        return $response;
    }
}
