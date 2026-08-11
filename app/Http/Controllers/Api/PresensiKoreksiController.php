<?php

namespace App\Http\Controllers\Api;

use App\Models\Presensi;
use App\Models\PresensiKoreksi;
use App\Services\AuditTrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PresensiKoreksiController extends ApiController
{
    public function __construct(private AuditTrailService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = PresensiKoreksi::with(['user:id,name,nip', 'presensi']);

        if ($user->roleSlug() === 'pegawai') {
            $query->where('id_user', $user->id);
        } elseif (in_array($user->roleSlug(), ['supervisor', 'admin_kantor'])) {
            $bawahanIds = $user->bawahan()->pluck('id')->push($user->id);
            $query->whereIn('id_user', $bawahanIds);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->query('per_page', 25), 200);

        return response()->json($query->latest('id')->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_presensi' => 'required|integer|exists:t_presensi,id',
            'tanggal' => 'required|date',
            'jam_masuk_koreksi' => 'nullable|date_format:H:i',
            'jam_keluar_koreksi' => 'nullable|date_format:H:i',
            'alasan' => 'required|string|max:500',
            'lampiran' => 'nullable|string|max:255',
        ]);

        $presensi = Presensi::findOrFail($data['id_presensi']);
        if ($presensi->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Tidak memiliki akses.'], 403);
        }

        $data['id_user'] = $request->user()->id;
        $data['status'] = 'menunggu';

        $model = PresensiKoreksi::create($data);
        $this->audit->log('create', 'presensi', $model->getTable(), $model->id, null, $model->toArray());

        return response()->json(['message' => 'Koreksi presensi diajukan.', 'data' => $model], 201);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $koreksi = PresensiKoreksi::findOrFail($id);

        if ($koreksi->status !== 'menunggu') {
            return response()->json(['message' => 'Koreksi sudah diproses.'], 422);
        }

        $old = $koreksi->toArray();
        $koreksi->update([
            'status' => 'disetujui',
            'id_approver' => $request->user()->id,
            'waktu_approval' => now(),
            'catatan_approval' => $request->catatan,
        ]);

        $presensi = $koreksi->presensi;
        if ($presensi) {
            $presensiOld = $presensi->toArray();
            $updateData = [];
            if ($koreksi->jam_masuk_koreksi) {
                $updateData['jam_masuk'] = $presensi->tanggal->format('Y-m-d') . ' ' . $koreksi->jam_masuk_koreksi;
            }
            if ($koreksi->jam_keluar_koreksi) {
                $updateData['jam_keluar'] = $presensi->tanggal->format('Y-m-d') . ' ' . $koreksi->jam_keluar_koreksi;
            }
            if ($updateData) {
                $presensi->update($updateData);
                $this->audit->log('koreksi_applied', 'presensi', $presensi->getTable(), $presensi->id, $presensiOld, $presensi->toArray());
            }
        }

        $this->audit->log('approve', 'presensi', $koreksi->getTable(), $koreksi->id, $old, $koreksi->toArray());

        return response()->json(['message' => 'Koreksi disetujui.', 'data' => $koreksi]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate(['catatan' => 'nullable|string|max:500']);

        $koreksi = PresensiKoreksi::findOrFail($id);

        if ($koreksi->status !== 'menunggu') {
            return response()->json(['message' => 'Koreksi sudah diproses.'], 422);
        }

        $old = $koreksi->toArray();
        $koreksi->update([
            'status' => 'ditolak',
            'id_approver' => $request->user()->id,
            'waktu_approval' => now(),
            'catatan_approval' => $request->catatan,
        ]);

        $this->audit->log('reject', 'presensi', $koreksi->getTable(), $koreksi->id, $old, $koreksi->toArray());

        return response()->json(['message' => 'Koreksi ditolak.', 'data' => $koreksi]);
    }
}
