<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class ForceHttpsInProduction
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production') && ! $request->isSecure()) {
            return new RedirectResponse('https://' . $request->getHttpHost() . $request->getRequestUri(), 301);
        }
        return $next($request);
    }
}
