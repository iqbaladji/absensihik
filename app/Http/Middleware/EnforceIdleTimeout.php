<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceIdleTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();
        $idleMinutes = (int) config('sanctum.idle_minutes', 30);

        if ($token && $idleMinutes > 0 && $token->last_used_at) {
            if ($token->last_used_at->diffInMinutes(now()) >= $idleMinutes) {
                $token->delete();

                return response()->json([
                    'message' => 'Sesi berakhir karena tidak aktif. Silakan masuk kembali.',
                ], 401);
            }
        }

        if ($user) {
            $user->forceFill(['last_activity_at' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
