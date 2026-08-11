<?php

namespace App\Http\Controllers\Api;

use App\Models\BlockLeave;
use App\Services\ApprovalService;
use App\Services\AuditTrailService;
use App\Services\CutiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockLeaveController extends ApiController
{
    public function __construct(
        private AuditTrailService $audit,
        private ApprovalService $approval,
        private CutiService $cutiService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = BlockLeave::with(['user:id,name,nip', 'periode']);

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
            'data' => BlockLeave::with(['user:id,name,nip', 'periode'])->findOrFail($id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_periode' => 'nullable|integer|exists:t_block_leave_periode,id',
            'tanggal_mulai' => 'required|date|after_or_equal:today',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'alasan' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        $start = Carbon::parse($data['tanggal_mulai']);
        if (empty($data['tanggal_selesai'])) {
            $data['tanggal_selesai'] = $this->cutiService->addWorkingDays($start, 4)->toDateString();
        }
        $end = Carbon::parse($data['tanggal_selesai']);

        $validation = $this->cutiService->validateBlockLeave($start, $end);
        if (! $validation['valid']) {
            return response()->json(['message' => $validation['message']], 422);
        }

        if (! $this->cutiService->hasSufficientBalance($user->id, 5)) {
            return response()->json(['message' => 'Saldo cuti tidak mencukupi (minimum 5 hari).'], 422);
        }

        $data['id_user'] = $user->id;
        $data['jumlah_hari_kerja'] = 5;
        $data['status'] = 'menunggu';
        $data['approval_snapshot'] = $this->approval->createSnapshot('block_leave', $user);

        $model = BlockLeave::create($data);
        $this->audit->log('create', 'block_leave', $model->getTable(), $model->id, null, $model->toArray());

        return response()->json(['message' => 'Pengajuan block leave tersimpan.', 'data' => $model], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = BlockLeave::findOrFail($id);

        if ($model->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Tidak memiliki akses.'], 403);
        }
        if ($model->status !== 'menunggu') {
            return response()->json(['message' => 'Pengajuan sudah diproses, tidak dapat diubah.'], 422);
        }

        $old = $model->toArray();
        $data = $request->validate([
            'id_periode' => 'required|integer|exists:t_block_leave_periode,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'nullable|string|max:1000',
        ]);

        $validation = $this->cutiService->validateBlockLeave(
            Carbon::parse($data['tanggal_mulai']),
            Carbon::parse($data['tanggal_selesai']),
        );
        if (! $validation['valid']) {
            return response()->json(['message' => $validation['message']], 422);
        }

        $data['jumlah_hari_kerja'] = 5;
        $model->update($data);
        $this->audit->log('update', 'block_leave', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Pengajuan diperbarui.', 'data' => $model]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $model = BlockLeave::findOrFail($id);

        if ($model->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Tidak memiliki akses.'], 403);
        }
        if ($model->status !== 'menunggu') {
            return response()->json(['message' => 'Pengajuan sudah diproses, tidak dapat dihapus.'], 422);
        }

        $old = $model->toArray();
        $model->update(['status' => 'dibatalkan']);
        $this->audit->log('cancel', 'block_leave', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Pengajuan dibatalkan.']);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $request->validate(['catatan' => 'nullable|string|max:500']);
        $model = BlockLeave::findOrFail($id);

        if ($model->status !== 'menunggu') {
            return response()->json(['message' => 'Pengajuan sudah diproses.'], 422);
        }

        $old = $model->toArray();
        $model->update(['status' => 'disetujui']);

        $this->cutiService->deductBalance($model->id_user, $model->jumlah_hari_kerja);
        $this->approval->logApproval($model->getTable(), $model->id, $request->user(), 'approve', $request->catatan);
        $this->audit->log('approve', 'block_leave', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Pengajuan disetujui.', 'data' => $model]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate(['catatan' => 'nullable|string|max:500']);
        $model = BlockLeave::findOrFail($id);

        if ($model->status !== 'menunggu') {
            return response()->json(['message' => 'Pengajuan sudah diproses.'], 422);
        }

        $old = $model->toArray();
        $model->update(['status' => 'ditolak']);

        $this->approval->logApproval($model->getTable(), $model->id, $request->user(), 'reject', $request->catatan);
        $this->audit->log('reject', 'block_leave', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Pengajuan ditolak.', 'data' => $model]);
    }
}
