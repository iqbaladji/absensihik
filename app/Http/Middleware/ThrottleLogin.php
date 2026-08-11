<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        $username = $request->input('username', '');
        $key = 'login:' . $request->ip() . '|' . mb_strtolower($username);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
            ], 429);
        }

        RateLimiter::hit($key, 60);

        $response = $next($request);

        if ($response->getStatusCode() === 200) {
            RateLimiter::clear($key);
        }

        return $response;
    }
}
