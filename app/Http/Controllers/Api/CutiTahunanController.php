<?php

namespace App\Http\Controllers\Api;

use App\Models\CutiTahunan;
use App\Services\ApprovalService;
use App\Services\AuditTrailService;
use App\Services\CutiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CutiTahunanController extends ApiController
{
    public function __construct(
        private AuditTrailService $audit,
        private ApprovalService $approval,
        private CutiService $cutiService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = CutiTahunan::with('user:id,name,nip');

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
        return response()->json(['data' => CutiTahunan::with('user:id,name,nip')->findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tanggal_mulai' => 'required|date|after_or_equal:today',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string|max:1000',
            'lampiran' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $jumlahHari = $this->cutiService->countWorkingDays(
            Carbon::parse($data['tanggal_mulai']),
            Carbon::parse($data['tanggal_selesai']),
        );

        if (! $this->cutiService->hasSufficientBalance($user->id, $jumlahHari)) {
            return response()->json(['message' => 'Saldo cuti tidak mencukupi.'], 422);
        }

        $data['id_user'] = $user->id;
        $data['jumlah_hari'] = $jumlahHari;
        $data['status'] = 'menunggu';
        $data['approval_snapshot'] = $this->approval->createSnapshot('cuti_tahunan', $user);

        $model = CutiTahunan::create($data);
        $this->audit->log('create', 'cuti_tahunan', $model->getTable(), $model->id, null, $model->toArray());

        return response()->json(['message' => 'Pengajuan cuti tersimpan.', 'data' => $model], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = CutiTahunan::findOrFail($id);

        if ($model->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Tidak memiliki akses.'], 403);
        }
        if ($model->status !== 'menunggu') {
            return response()->json(['message' => 'Pengajuan sudah diproses, tidak dapat diubah.'], 422);
        }

        $old = $model->toArray();
        $data = $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string|max:1000',
            'lampiran' => 'nullable|string|max:255',
        ]);

        $jumlahHari = $this->cutiService->countWorkingDays(
            Carbon::parse($data['tanggal_mulai']),
            Carbon::parse($data['tanggal_selesai']),
        );
        $data['jumlah_hari'] = $jumlahHari;

        $model->update($data);
        $this->audit->log('update', 'cuti_tahunan', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Pengajuan diperbarui.', 'data' => $model]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $model = CutiTahunan::findOrFail($id);

        if ($model->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Tidak memiliki akses.'], 403);
        }
        if ($model->status !== 'menunggu') {
            return response()->json(['message' => 'Pengajuan sudah diproses, tidak dapat dihapus.'], 422);
        }

        $old = $model->toArray();
        $model->update(['status' => 'dibatalkan']);
        $this->audit->log('cancel', 'cuti_tahunan', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Pengajuan dibatalkan.']);
    }

    public function saldo(Request $request): JsonResponse
    {
        $saldo = $this->cutiService->getSaldo(
            $request->user()->id,
            $request->query('tahun') ? (int) $request->query('tahun') : null,
        );

        return $this->ok($saldo);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $request->validate(['catatan' => 'nullable|string|max:500']);
        $model = CutiTahunan::findOrFail($id);

        if ($model->status !== 'menunggu') {
            return response()->json(['message' => 'Pengajuan sudah diproses.'], 422);
        }

        $old = $model->toArray();
        $model->update(['status' => 'disetujui']);

        $this->cutiService->deductBalance($model->id_user, $model->jumlah_hari);
        $this->approval->logApproval($model->getTable(), $model->id, $request->user(), 'approve', $request->catatan);
        $this->audit->log('approve', 'cuti_tahunan', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Pengajuan disetujui.', 'data' => $model]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate(['catatan' => 'nullable|string|max:500']);
        $model = CutiTahunan::findOrFail($id);

        if ($model->status !== 'menunggu') {
            return response()->json(['message' => 'Pengajuan sudah diproses.'], 422);
        }

        $old = $model->toArray();
        $model->update(['status' => 'ditolak']);

        $this->approval->logApproval($model->getTable(), $model->id, $request->user(), 'reject', $request->catatan);
        $this->audit->log('reject', 'cuti_tahunan', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Pengajuan ditolak.', 'data' => $model]);
    }
}
