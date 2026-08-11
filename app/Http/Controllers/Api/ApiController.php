<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    protected function scopeKantorId(Request $request): ?int
    {
        $user = $request->user();
        return $user && $user->isOrgScoped() ? $user->id_kantor : null;
    }

    protected function ok(mixed $data = null, string $message = 'OK', int $status = 200)
    {
        return response()->json(['message' => $message, 'data' => $data], $status);
    }
}
