<?php

namespace App\Http\Controllers\Api;

use App\Models\Lembur;
use App\Services\ApprovalService;
use App\Services\AuditTrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LemburController extends ApiController
{
    public function __construct(
        private AuditTrailService $audit,
        private ApprovalService $approval,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Lembur::with('user:id,name,nip');

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

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => Lembur::with('user:id,name,nip')->findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tanggal' => 'required|date',
            'jam_mulai_rencana' => 'required|date_format:H:i',
            'jam_selesai_rencana' => 'required|date_format:H:i|after:jam_mulai_rencana',
            'durasi_rencana' => 'nullable|numeric|min:0.5',
            'uraian_pekerjaan' => 'required|string|max:1000',
            'lampiran' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $data['id_user'] = $user->id;
        $data['status'] = 'menunggu';
        $data['approval_snapshot'] = $this->approval->createSnapshot('lembur', $user);

        $model = Lembur::create($data);
        $this->audit->log('create', 'lembur', $model->getTable(), $model->id, null, $model->toArray());

        return response()->json(['message' => 'Pengajuan lembur tersimpan.', 'data' => $model], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = Lembur::findOrFail($id);

        if ($model->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Tidak memiliki akses.'], 403);
        }
        if ($model->status !== 'menunggu') {
            return response()->json(['message' => 'Pengajuan sudah diproses, tidak dapat diubah.'], 422);
        }

        $old = $model->toArray();
        $data = $request->validate([
            'tanggal' => 'required|date',
            'jam_mulai_rencana' => 'required|date_format:H:i',
            'jam_selesai_rencana' => 'required|date_format:H:i|after:jam_mulai_rencana',
            'durasi_rencana' => 'nullable|numeric|min:0.5',
            'uraian_pekerjaan' => 'required|string|max:1000',
            'lampiran' => 'nullable|string|max:255',
        ]);

        $model->update($data);
        $this->audit->log('update', 'lembur', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Pengajuan diperbarui.', 'data' => $model]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $model = Lembur::findOrFail($id);

        if ($model->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Tidak memiliki akses.'], 403);
        }
        if ($model->status !== 'menunggu') {
            return response()->json(['message' => 'Pengajuan sudah diproses, tidak dapat dihapus.'], 422);
        }

        $old = $model->toArray();
        $model->update(['status' => 'dibatalkan']);
        $this->audit->log('cancel', 'lembur', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Pengajuan dibatalkan.']);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $request->validate(['catatan' => 'nullable|string|max:500']);
        $model = Lembur::findOrFail($id);

        if ($model->status !== 'menunggu') {
            return response()->json(['message' => 'Pengajuan sudah diproses.'], 422);
        }

        $old = $model->toArray();
        $model->update(['status' => 'disetujui']);

        $this->approval->logApproval($model->getTable(), $model->id, $request->user(), 'approve', $request->catatan);
        $this->audit->log('approve', 'lembur', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Pengajuan disetujui.', 'data' => $model]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate(['catatan' => 'nullable|string|max:500']);
        $model = Lembur::findOrFail($id);

        if ($model->status !== 'menunggu') {
            return response()->json(['message' => 'Pengajuan sudah diproses.'], 422);
        }

        $old = $model->toArray();
        $model->update(['status' => 'ditolak']);

        $this->approval->logApproval($model->getTable(), $model->id, $request->user(), 'reject', $request->catatan);
        $this->audit->log('reject', 'lembur', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Pengajuan ditolak.', 'data' => $model]);
    }

    public function mulai(Request $request, int $id): JsonResponse
    {
        $model = Lembur::findOrFail($id);

        if ($model->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Tidak memiliki akses.'], 403);
        }
        if ($model->status !== 'disetujui') {
            return response()->json(['message' => 'Lembur belum disetujui.'], 422);
        }
        if ($model->jam_mulai_aktual) {
            return response()->json(['message' => 'Lembur sudah dimulai.'], 422);
        }

        $old = $model->toArray();
        $model->update([
            'jam_mulai_aktual' => now(),
            'status' => 'berlangsung',
        ]);

        $this->audit->log('mulai', 'lembur', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Lembur dimulai.', 'data' => $model]);
    }

    public function selesai(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'hasil_pekerjaan' => 'required|string|max:1000',
        ]);

        $model = Lembur::findOrFail($id);

        if ($model->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Tidak memiliki akses.'], 403);
        }
        if ($model->status !== 'berlangsung') {
            return response()->json(['message' => 'Lembur belum dimulai.'], 422);
        }
        if ($model->jam_selesai_aktual) {
            return response()->json(['message' => 'Lembur sudah selesai.'], 422);
        }

        $old = $model->toArray();
        $jamSelesai = now();
        $durasiAktual = $model->jam_mulai_aktual->diffInMinutes($jamSelesai) / 60;

        $model->update([
            'jam_selesai_aktual' => $jamSelesai,
            'durasi_aktual' => round($durasiAktual, 2),
            'hasil_pekerjaan' => $request->hasil_pekerjaan,
            'status' => 'selesai',
        ]);

        $this->audit->log('selesai', 'lembur', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Lembur selesai.', 'data' => $model]);
    }
}
