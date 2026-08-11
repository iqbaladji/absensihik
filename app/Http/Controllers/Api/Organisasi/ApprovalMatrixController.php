<?php

namespace App\Http\Controllers\Api\Organisasi;

use App\Http\Controllers\Api\ApiController;
use App\Models\ApprovalMatrix;
use App\Services\AuditTrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalMatrixController extends ApiController
{
    public function __construct(private AuditTrailService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = ApprovalMatrix::with(['jabatanPemohon', 'unit', 'jabatanApprover', 'userApprover']);

        if ($modul = $request->query('modul')) {
            $query->where('modul', $modul);
        }

        if ($request->has('is_aktif')) {
            $query->where('is_aktif', $request->boolean('is_aktif'));
        }

        $perPage = min((int) $request->query('per_page', 25), 200);

        return response()->json($query->orderBy('modul')->orderBy('urutan')->paginate($perPage));
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'data' => ApprovalMatrix::with(['jabatanPemohon', 'unit', 'jabatanApprover', 'userApprover'])->findOrFail($id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'modul' => 'required|string|max:50',
            'id_jabatan_pemohon' => 'nullable|integer|exists:m_jabatan,id',
            'id_unit' => 'nullable|integer|exists:m_unit_kerja,id',
            'urutan' => 'required|integer|min:1',
            'tipe_approver' => 'required|in:atasan_langsung,jabatan,user',
            'id_jabatan_approver' => 'nullable|integer|exists:m_jabatan,id',
            'id_user_approver' => 'nullable|integer|exists:users,id',
            'is_aktif' => 'sometimes|boolean',
        ]);

        $model = ApprovalMatrix::create($data);
        $this->audit->log('create', 'organisasi', $model->getTable(), $model->id, null, $model->toArray());

        return response()->json(['message' => 'Data tersimpan.', 'data' => $model], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = ApprovalMatrix::findOrFail($id);
        $old = $model->toArray();

        $data = $request->validate([
            'modul' => 'required|string|max:50',
            'id_jabatan_pemohon' => 'nullable|integer|exists:m_jabatan,id',
            'id_unit' => 'nullable|integer|exists:m_unit_kerja,id',
            'urutan' => 'required|integer|min:1',
            'tipe_approver' => 'required|in:atasan_langsung,jabatan,user',
            'id_jabatan_approver' => 'nullable|integer|exists:m_jabatan,id',
            'id_user_approver' => 'nullable|integer|exists:users,id',
            'is_aktif' => 'sometimes|boolean',
        ]);

        $model->update($data);
        $this->audit->log('update', 'organisasi', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Data diperbarui.', 'data' => $model]);
    }

    public function destroy(int $id): JsonResponse
    {
        $model = ApprovalMatrix::findOrFail($id);
        $old = $model->toArray();
        $model->update(['is_aktif' => false]);
        $this->audit->log('nonaktif', 'organisasi', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Data dinonaktifkan.', 'data' => $model]);
    }
}
