<?php

namespace App\Http\Controllers\Api;

use App\Models\Presensi;
use App\Services\AuditTrailService;
use App\Services\PresensiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PresensiController extends ApiController
{
    public function __construct(
        private AuditTrailService $audit,
        private PresensiService $presensiService,
    ) {}

    public function clockIn(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'akurasi' => 'required|numeric|min:0',
            'foto' => 'required|string',
            'device_id' => 'required|string|max:100',
            'device_model' => 'nullable|string|max:100',
            'tipe' => 'nullable|in:di_kantor,dinas_luar,wfh,wfa',
        ]);

        $user = $request->user();
        $today = now()->toDateString();

        $existing = Presensi::where('id_user', $user->id)->whereDate('tanggal', $today)->first();
        if ($existing) {
            return response()->json(['message' => 'Anda sudah melakukan clock-in hari ini.'], 422);
        }

        $kantor = $user->kantor;
        if (! $kantor) {
            return response()->json(['message' => 'Kantor belum ditetapkan.'], 422);
        }

        $check = $this->presensiService->checkRadius(
            $request->latitude,
            $request->longitude,
            $request->akurasi,
            $kantor,
        );

        if (! $check['within'] && ! $request->tipe) {
            return response()->json([
                'message' => 'Lokasi di luar radius kantor. Pilih tipe presensi.',
                'data' => [
                    'needs_choice' => true,
                    'jarak' => $check['distance'],
                    'radius' => $kantor->radius,
                    'tipe_tersedia' => ['dinas_luar', 'wfh', 'wfa'],
                ],
            ], 422);
        }

        $tipe = $check['within'] ? 'di_kantor' : ($request->tipe ?? 'di_kantor');
        $jamMasuk = now();

        $shift = $user->id_jadwal
            ? $this->presensiService->getShiftForDay($user->id_jadwal, Carbon::today())
            : null;
        $statusMasuk = $this->presensiService->determineStatusMasuk($jamMasuk, $shift);

        $fotoPath = null;
        if ($request->foto) {
            $fotoPath = 'presensi/' . $user->id . '/' . $today . '_masuk.jpg';
            Storage::put($fotoPath, base64_decode($request->foto));
        }

        $presensi = Presensi::create([
            'id_user' => $user->id,
            'id_kantor' => $kantor->id,
            'tanggal' => $today,
            'jam_masuk' => $jamMasuk,
            'lat_masuk' => $request->latitude,
            'lng_masuk' => $request->longitude,
            'accuracy_masuk' => $request->akurasi,
            'jarak_masuk' => $check['distance'],
            'foto_masuk' => $fotoPath,
            'device_id' => $request->device_id,
            'device_model' => $request->device_model,
            'tipe' => $tipe,
            'status_masuk' => $statusMasuk,
            'perlu_verifikasi' => $check['perlu_verifikasi'],
        ]);

        $this->audit->log('clock_in', 'presensi', $presensi->getTable(), $presensi->id, null, $presensi->toArray());

        return response()->json([
            'message' => 'Clock-in berhasil.',
            'data' => $presensi,
        ], 201);
    }

    public function clockOut(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'akurasi' => 'required|numeric|min:0',
            'foto' => 'required|string',
            'device_id' => 'required|string|max:100',
            'device_model' => 'nullable|string|max:100',
        ]);

        $user = $request->user();
        $today = now()->toDateString();

        $presensi = Presensi::where('id_user', $user->id)->whereDate('tanggal', $today)->first();
        if (! $presensi) {
            return response()->json(['message' => 'Belum melakukan clock-in hari ini.'], 422);
        }
        if ($presensi->jam_keluar) {
            return response()->json(['message' => 'Anda sudah melakukan clock-out hari ini.'], 422);
        }

        $kantor = $user->kantor;
        $check = $this->presensiService->checkRadius(
            $request->latitude,
            $request->longitude,
            $request->akurasi,
            $kantor,
        );

        $jamKeluar = now();
        $shift = $user->id_jadwal
            ? $this->presensiService->getShiftForDay($user->id_jadwal, Carbon::today())
            : null;
        $statusKeluar = $this->presensiService->determineStatusKeluar($jamKeluar, $shift);

        $fotoPath = 'presensi/' . $user->id . '/' . $today . '_keluar.jpg';
        Storage::put($fotoPath, base64_decode($request->foto));

        $old = $presensi->toArray();
        $presensi->update([
            'jam_keluar' => $jamKeluar,
            'lat_keluar' => $request->latitude,
            'lng_keluar' => $request->longitude,
            'accuracy_keluar' => $request->akurasi,
            'jarak_keluar' => $check['distance'],
            'foto_keluar' => $fotoPath,
            'status_keluar' => $statusKeluar,
        ]);

        $this->audit->log('clock_out', 'presensi', $presensi->getTable(), $presensi->id, $old, $presensi->toArray());

        return response()->json(['message' => 'Clock-out berhasil.', 'data' => $presensi]);
    }

    public function today(Request $request): JsonResponse
    {
        $presensi = Presensi::where('id_user', $request->user()->id)
            ->whereDate('tanggal', now()->toDateString())
            ->first();

        return $this->ok($presensi);
    }

    public function riwayat(Request $request): JsonResponse
    {
        $request->validate([
            'dari' => 'nullable|date',
            'sampai' => 'nullable|date|after_or_equal:dari',
        ]);

        $query = Presensi::where('id_user', $request->user()->id);

        if ($request->dari) {
            $query->where('tanggal', '>=', $request->dari);
        }
        if ($request->sampai) {
            $query->where('tanggal', '<=', $request->sampai);
        }

        $perPage = min((int) $request->query('per_page', 25), 200);

        return response()->json($query->latest('tanggal')->paginate($perPage));
    }

    public function tim(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'tanggal' => 'nullable|date',
        ]);

        $tanggal = $request->tanggal ?? now()->toDateString();
        $bawahanIds = $user->bawahan()->pluck('id');

        $query = Presensi::with('user:id,name,nip')
            ->whereIn('id_user', $bawahanIds)
            ->whereDate('tanggal', $tanggal);

        $perPage = min((int) $request->query('per_page', 25), 200);

        return response()->json($query->latest('id')->paginate($perPage));
    }

    public function verify(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'aksi' => 'required|in:approve,reject',
            'catatan' => 'nullable|string|max:500',
        ]);

        $presensi = Presensi::findOrFail($id);

        if (! $presensi->perlu_verifikasi) {
            return response()->json(['message' => 'Presensi ini tidak memerlukan verifikasi.'], 422);
        }

        $old = $presensi->toArray();
        $presensi->update([
            'perlu_verifikasi' => $request->aksi === 'reject',
            'id_verifikator' => $request->user()->id,
            'waktu_verifikasi' => now(),
            'catatan_verifikasi' => $request->catatan,
        ]);

        $this->audit->log('verify_' . $request->aksi, 'presensi', $presensi->getTable(), $presensi->id, $old, $presensi->toArray());

        $msg = $request->aksi === 'approve' ? 'Presensi diverifikasi.' : 'Presensi ditolak.';

        return response()->json(['message' => $msg, 'data' => $presensi]);
    }
}
