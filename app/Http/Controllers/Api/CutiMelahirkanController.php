<?php

namespace App\Http\Controllers\Api;

use App\Models\CutiMelahirkan;
use App\Services\ApprovalService;
use App\Services\AuditTrailService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CutiMelahirkanController extends ApiController
{
    public function __construct(
        private AuditTrailService $audit,
        private ApprovalService $approval,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = CutiMelahirkan::with('user:id,name,nip');

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
        return response()->json(['data' => CutiMelahirkan::with('user:id,name,nip')->findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'jumlah_hari' => 'required|integer|min:1',
            'tipe' => 'required|in:melahirkan,keguguran',
            'catatan' => 'nullable|string|max:1000',
            'lampiran' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        if (empty($data['tanggal_selesai'])) {
            $data['tanggal_selesai'] = Carbon::parse($data['tanggal_mulai'])->addDays($data['jumlah_hari'] - 1)->toDateString();
        }
        $data['id_user'] = $user->id;
        $data['status'] = 'menunggu';
        $data['approval_snapshot'] = $this->approval->createSnapshot('cuti_melahirkan', $user);

        $model = CutiMelahirkan::create($data);
        $this->audit->log('create', 'cuti_melahirkan', $model->getTable(), $model->id, null, $model->toArray());

        return response()->json(['message' => 'Pengajuan cuti melahirkan tersimpan.', 'data' => $model], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = CutiMelahirkan::findOrFail($id);

        if ($model->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Tidak memiliki akses.'], 403);
        }
        if ($model->status !== 'menunggu') {
            return response()->json(['message' => 'Pengajuan sudah diproses, tidak dapat diubah.'], 422);
        }

        $old = $model->toArray();
        $data = $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'jumlah_hari' => 'required|integer|min:1',
            'tipe' => 'required|in:melahirkan,keguguran',
            'catatan' => 'nullable|string|max:1000',
            'lampiran' => 'nullable|string|max:255',
        ]);

        $model->update($data);
        $this->audit->log('update', 'cuti_melahirkan', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Pengajuan diperbarui.', 'data' => $model]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $model = CutiMelahirkan::findOrFail($id);

        if ($model->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Tidak memiliki akses.'], 403);
        }
        if ($model->status !== 'menunggu') {
            return response()->json(['message' => 'Pengajuan sudah diproses, tidak dapat dihapus.'], 422);
        }

        $old = $model->toArray();
        $model->update(['status' => 'dibatalkan']);
        $this->audit->log('cancel', 'cuti_melahirkan', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Pengajuan dibatalkan.']);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'catatan' => 'nullable|string|max:500',
            'level' => 'sometimes|integer|in:1,2',
        ]);
        $model = CutiMelahirkan::findOrFail($id);

        if (! in_array($model->status, ['menunggu', 'disetujui_level1'])) {
            return response()->json(['message' => 'Pengajuan sudah diproses.'], 422);
        }

        $old = $model->toArray();
        $urutan = $model->status === 'menunggu' ? 1 : 2;
        $newStatus = $urutan === 1 ? 'disetujui_level1' : 'disetujui';

        $model->update(['status' => $newStatus]);

        $this->approval->logApproval($model->getTable(), $model->id, $request->user(), 'approve', $request->catatan, $urutan);
        $this->audit->log('approve_level' . $urutan, 'cuti_melahirkan', $model->getTable(), $model->id, $old, $model->toArray());

        $msg = $urutan === 1 ? 'Disetujui level 1, menunggu persetujuan level 2.' : 'Pengajuan disetujui.';

        return response()->json(['message' => $msg, 'data' => $model]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate(['catatan' => 'nullable|string|max:500']);
        $model = CutiMelahirkan::findOrFail($id);

        if (! in_array($model->status, ['menunggu', 'disetujui_level1'])) {
            return response()->json(['message' => 'Pengajuan sudah diproses.'], 422);
        }

        $old = $model->toArray();
        $model->update(['status' => 'ditolak']);

        $this->approval->logApproval($model->getTable(), $model->id, $request->user(), 'reject', $request->catatan);
        $this->audit->log('reject', 'cuti_melahirkan', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Pengajuan ditolak.', 'data' => $model]);
    }
}
