<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $modul, string $ability = 'R'): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Tidak terotentikasi.'], 401);
        }

        if ($user->status !== 'aktif') {
            return response()->json(['message' => 'Akun nonaktif.'], 403);
        }

        if (! $user->hasAccess($modul, $ability)) {
            return response()->json([
                'message' => "Akses ditolak: tidak memiliki hak '{$ability}' pada modul '{$modul}'.",
            ], 403);
        }

        return $next($request);
    }
}
