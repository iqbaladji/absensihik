<?php

namespace App\Http\Controllers\Api;

use App\Models\Izin;
use App\Models\JenisIzin;
use App\Services\ApprovalService;
use App\Services\AuditTrailService;
use App\Services\CutiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IzinController extends ApiController
{
    public function __construct(
        private AuditTrailService $audit,
        private ApprovalService $approval,
        private CutiService $cutiService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Izin::with(['user:id,name,nip', 'jenisIzin']);

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
        return response()->json([
            'data' => Izin::with(['user:id,name,nip', 'jenisIzin'])->findOrFail($id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_jenis_izin' => 'required|integer|exists:m_jenis_izin,id',
            'tanggal_mulai' => 'required|date|after_or_equal:today',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string|max:1000',
            'lampiran' => 'nullable|string|max:255',
        ]);

        $jenisIzin = JenisIzin::findOrFail($data['id_jenis_izin']);

        if ($jenisIzin->perlu_lampiran && ! $request->lampiran) {
            return response()->json(['message' => 'Jenis izin ini memerlukan lampiran.'], 422);
        }

        $user = $request->user();
        $jumlahHari = $this->cutiService->countWorkingDays(
            \Carbon\Carbon::parse($data['tanggal_mulai']),
            \Carbon\Carbon::parse($data['tanggal_selesai']),
        );

        if ($jenisIzin->maks_hari && $jumlahHari > $jenisIzin->maks_hari) {
            return response()->json([
                'message' => "Maksimal izin untuk jenis ini adalah {$jenisIzin->maks_hari} hari kerja.",
            ], 422);
        }

        if ($jenisIzin->potong_cuti && ! $this->cutiService->hasSufficientBalance($user->id, $jumlahHari)) {
            return response()->json(['message' => 'Saldo cuti tidak mencukupi.'], 422);
        }

        $data['id_user'] = $user->id;
        $data['jumlah_hari'] = $jumlahHari;
        $data['status'] = 'menunggu';
        $data['approval_snapshot'] = $this->approval->createSnapshot('izin', $user);

        $model = Izin::create($data);
        $this->audit->log('create', 'izin', $model->getTable(), $model->id, null, $model->toArray());

        return response()->json(['message' => 'Pengajuan izin tersimpan.', 'data' => $model], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = Izin::findOrFail($id);

        if ($model->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Tidak memiliki akses.'], 403);
        }
        if ($model->status !== 'menunggu') {
            return response()->json(['message' => 'Pengajuan sudah diproses, tidak dapat diubah.'], 422);
        }

        $old = $model->toArray();
        $data = $request->validate([
            'id_jenis_izin' => 'required|integer|exists:m_jenis_izin,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string|max:1000',
            'lampiran' => 'nullable|string|max:255',
        ]);

        $jumlahHari = $this->cutiService->countWorkingDays(
            \Carbon\Carbon::parse($data['tanggal_mulai']),
            \Carbon\Carbon::parse($data['tanggal_selesai']),
        );
        $data['jumlah_hari'] = $jumlahHari;

        $model->update($data);
        $this->audit->log('update', 'izin', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Pengajuan diperbarui.', 'data' => $model]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $model = Izin::findOrFail($id);

        if ($model->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Tidak memiliki akses.'], 403);
        }
        if ($model->status !== 'menunggu') {
            return response()->json(['message' => 'Pengajuan sudah diproses, tidak dapat dihapus.'], 422);
        }

        $old = $model->toArray();
        $model->update(['status' => 'dibatalkan']);
        $this->audit->log('cancel', 'izin', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Pengajuan dibatalkan.']);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $request->validate(['catatan' => 'nullable|string|max:500']);
        $model = Izin::findOrFail($id);

        if ($model->status !== 'menunggu') {
            return response()->json(['message' => 'Pengajuan sudah diproses.'], 422);
        }

        $old = $model->toArray();
        $model->update(['status' => 'disetujui']);

        $jenisIzin = $model->jenisIzin;
        if ($jenisIzin && $jenisIzin->potong_cuti) {
            $this->cutiService->deductBalance($model->id_user, $model->jumlah_hari);
        }

        $this->approval->logApproval($model->getTable(), $model->id, $request->user(), 'approve', $request->catatan);
        $this->audit->log('approve', 'izin', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Pengajuan disetujui.', 'data' => $model]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate(['catatan' => 'nullable|string|max:500']);
        $model = Izin::findOrFail($id);

        if ($model->status !== 'menunggu') {
            return response()->json(['message' => 'Pengajuan sudah diproses.'], 422);
        }

        $old = $model->toArray();
        $model->update(['status' => 'ditolak']);

        $this->approval->logApproval($model->getTable(), $model->id, $request->user(), 'reject', $request->catatan);
        $this->audit->log('reject', 'izin', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Pengajuan ditolak.', 'data' => $model]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $model = Izin::findOrFail($id);

        if ($model->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Tidak memiliki akses.'], 403);
        }
        if (! in_array($model->status, ['menunggu', 'disetujui'])) {
            return response()->json(['message' => 'Pengajuan tidak dapat dibatalkan.'], 422);
        }

        $old = $model->toArray();
        $wasApproved = $model->status === 'disetujui';
        $model->update(['status' => 'dibatalkan']);

        if ($wasApproved) {
            $jenisIzin = $model->jenisIzin;
            if ($jenisIzin && $jenisIzin->potong_cuti) {
                $this->cutiService->restoreBalance($model->id_user, $model->jumlah_hari);
            }
        }

        $this->audit->log('cancel', 'izin', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Pengajuan dibatalkan.', 'data' => $model]);
    }
}
