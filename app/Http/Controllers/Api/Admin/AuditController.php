<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\AuditTrail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'dari' => 'nullable|date',
            'sampai' => 'nullable|date|after_or_equal:dari',
            'id_user' => 'nullable|integer|exists:users,id',
            'modul' => 'nullable|string|max:50',
            'aksi' => 'nullable|string|max:50',
        ]);

        $query = AuditTrail::with('user:id,name,username');

        if ($request->dari) {
            $query->where('waktu', '>=', $request->dari . ' 00:00:00');
        }
        if ($request->sampai) {
            $query->where('waktu', '<=', $request->sampai . ' 23:59:59');
        }
        if ($request->id_user) {
            $query->where('id_user', $request->id_user);
        }
        if ($request->modul) {
            $query->where('modul', $request->modul);
        }
        if ($request->aksi) {
            $query->where('aksi', $request->aksi);
        }

        $perPage = min((int) $request->query('per_page', 25), 200);

        return response()->json($query->latest('id')->paginate($perPage));
    }
}
