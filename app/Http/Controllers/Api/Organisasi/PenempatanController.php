<?php

namespace App\Http\Controllers\Api\Organisasi;

use App\Http\Controllers\Api\ApiController;
use App\Models\Penempatan;
use App\Services\AuditTrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PenempatanController extends ApiController
{
    public function __construct(private AuditTrailService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = Penempatan::with(['user', 'kantor', 'unit', 'jabatan']);

        if ($search = $request->query('q')) {
            $query->whereHas('user', function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        if ($kantorId = $this->scopeKantorId($request)) {
            $query->where('id_kantor', $kantorId);
        }

        if ($request->has('is_aktif')) {
            $query->where('is_aktif', $request->boolean('is_aktif'));
        }

        $perPage = min((int) $request->query('per_page', 25), 200);

        return response()->json($query->latest('id')->paginate($perPage));
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'data' => Penempatan::with(['user', 'kantor', 'unit', 'jabatan'])->findOrFail($id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_user' => 'required|integer|exists:users,id',
            'id_kantor' => 'required|integer|exists:m_kantor,id',
            'id_unit' => 'nullable|integer|exists:m_unit_kerja,id',
            'id_jabatan' => 'nullable|integer|exists:m_jabatan,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'is_aktif' => 'sometimes|boolean',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $model = Penempatan::create($data);
        $this->audit->log('create', 'organisasi', $model->getTable(), $model->id, null, $model->toArray());

        return response()->json([
            'message' => 'Data tersimpan.',
            'data' => $model->load(['user', 'kantor', 'unit', 'jabatan']),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = Penempatan::findOrFail($id);
        $old = $model->toArray();

        $data = $request->validate([
            'id_user' => 'required|integer|exists:users,id',
            'id_kantor' => 'required|integer|exists:m_kantor,id',
            'id_unit' => 'nullable|integer|exists:m_unit_kerja,id',
            'id_jabatan' => 'nullable|integer|exists:m_jabatan,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'is_aktif' => 'sometimes|boolean',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $model->update($data);
        $this->audit->log('update', 'organisasi', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json([
            'message' => 'Data diperbarui.',
            'data' => $model->load(['user', 'kantor', 'unit', 'jabatan']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $model = Penempatan::findOrFail($id);
        $old = $model->toArray();
        $model->update(['is_aktif' => false]);
        $this->audit->log('nonaktif', 'organisasi', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Penempatan dinonaktifkan.', 'data' => $model]);
    }
}
