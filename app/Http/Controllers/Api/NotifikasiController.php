<?php

namespace App\Http\Controllers\Api;

use App\Models\Notifikasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotifikasiController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Notifikasi::where('id_user', $request->user()->id);

        $perPage = min((int) $request->query('per_page', 25), 200);

        return response()->json($query->latest('id')->paginate($perPage));
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notifikasi::where('id_user', $request->user()->id)
            ->whereNull('dibaca_pada')
            ->count();

        return $this->ok(['count' => $count]);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $notif = Notifikasi::where('id_user', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        if (! $notif->dibaca_pada) {
            $notif->update(['dibaca_pada' => now()]);
        }

        return $this->ok(message: 'Notifikasi ditandai sudah dibaca.');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Notifikasi::where('id_user', $request->user()->id)
            ->whereNull('dibaca_pada')
            ->update(['dibaca_pada' => now()]);

        return $this->ok(message: 'Semua notifikasi ditandai sudah dibaca.');
    }
}
