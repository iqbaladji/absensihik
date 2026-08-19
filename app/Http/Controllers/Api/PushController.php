<?php

namespace App\Http\Controllers\Api;

use App\Models\PushSubscription;
use App\Services\PushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushController extends ApiController
{
    public function vapidKey(): JsonResponse
    {
        return response()->json(['public_key' => config('webpush.public_key')]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => 'required|string|max:2000',
            'keys.p256dh' => 'required|string|max:255',
            'keys.auth' => 'required|string|max:100',
        ]);

        $user = $request->user();

        PushSubscription::updateOrCreate(
            ['id_user' => $user->id, 'endpoint' => $data['endpoint']],
            [
                'p256dh' => $data['keys']['p256dh'],
                'auth' => $data['keys']['auth'],
                'user_agent' => substr($request->userAgent() ?? '', 0, 255),
            ],
        );

        return response()->json(['message' => 'Notifikasi diaktifkan.']);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => 'required|string']);
        PushSubscription::where('id_user', $request->user()->id)
            ->where('endpoint', $data['endpoint'])
            ->delete();
        return response()->json(['message' => 'Notifikasi dinonaktifkan.']);
    }

    public function status(Request $request): JsonResponse
    {
        $count = PushSubscription::where('id_user', $request->user()->id)->count();
        return response()->json(['enabled' => $count > 0, 'count' => $count]);
    }

    public function toggleAdzan(Request $request): JsonResponse
    {
        $data = $request->validate(['aktif' => 'required|boolean']);
        $user = $request->user();
        $user->update(['adzan_notif' => $data['aktif']]);
        return response()->json(['message' => $data['aktif'] ? 'Notif adzan aktif.' : 'Notif adzan mati.', 'aktif' => (bool) $user->adzan_notif]);
    }

    public function adzanStatus(Request $request): JsonResponse
    {
        return response()->json(['aktif' => (bool) $request->user()->adzan_notif]);
    }

    public function test(Request $request, PushService $push): JsonResponse
    {
        $push->sendToUser(
            $request->user()->id,
            'Uji Coba Notifikasi',
            'Kalau ini muncul di HP Anda, push notification berhasil aktif. 🎉',
            '/',
        );
        return response()->json(['message' => 'Test push dikirim.']);
    }
}
