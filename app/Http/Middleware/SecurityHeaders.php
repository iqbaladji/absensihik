<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(self), geolocation=(self), microphone=(), payment=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
        ];

        // HSTS: only enforce over HTTPS (avoid breaking local HTTP dev)
        if ($request->isSecure() && ! app()->environment('local')) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains; preload';
        }

        // Content-Security-Policy: tight for API-only, loose for the SPA HTML.
        // We serve Vue SPA — needs 'self' scripts, allow aladhan API + inline images.
        if (! $request->is('api/*')) {
            $csp = [
                "default-src 'self'",
                "script-src 'self'",
                "style-src 'self' 'unsafe-inline'",
                "img-src 'self' data: blob:",
                "font-src 'self' data:",
                "connect-src 'self' https://api.aladhan.com https://*.googleapis.com https://*.gstatic.com",
                "media-src 'self'",
                "frame-ancestors 'self'",
                "form-action 'self'",
                "base-uri 'self'",
                "object-src 'none'",
                "upgrade-insecure-requests",
            ];
            $headers['Content-Security-Policy'] = implode('; ', $csp);
        }

        foreach ($headers as $key => $value) {
            if (! $response->headers->has($key)) {
                $response->headers->set($key, $value);
            }
        }

        // Ensure server signature is not leaked
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
