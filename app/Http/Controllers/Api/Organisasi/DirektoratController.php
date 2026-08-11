<?php

namespace App\Http\Controllers\Api\Organisasi;

use App\Http\Controllers\Api\ApiController;
use App\Models\Direktorat;
use App\Services\AuditTrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DirektoratController extends ApiController
{
    public function __construct(private AuditTrailService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = Direktorat::with('entitas');

        if ($search = $request->query('q')) {
            $query->where(function ($w) use ($search) {
                $w->where('kode', 'like', "%{$search}%")
                    ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->query('per_page', 25), 200);

        return response()->json($query->latest('id')->paginate($perPage));
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => Direktorat::with('entitas')->findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_entitas' => 'required|integer|exists:m_entitas,id',
            'kode' => 'required|string|max:20|unique:m_direktorat,kode',
            'nama' => 'required|string|max:100',
            'status' => 'sometimes|in:aktif,nonaktif',
        ]);

        $model = Direktorat::create($data);
        $this->audit->log('create', 'organisasi', $model->getTable(), $model->id, null, $model->toArray());

        return response()->json(['message' => 'Data tersimpan.', 'data' => $model->load('entitas')], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = Direktorat::findOrFail($id);
        $old = $model->toArray();

        $data = $request->validate([
            'id_entitas' => 'required|integer|exists:m_entitas,id',
            'kode' => 'required|string|max:20|unique:m_direktorat,kode,' . $id,
            'nama' => 'required|string|max:100',
            'status' => 'sometimes|in:aktif,nonaktif',
        ]);

        $model->update($data);
        $this->audit->log('update', 'organisasi', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Data diperbarui.', 'data' => $model->load('entitas')]);
    }

    public function destroy(int $id): JsonResponse
    {
        $model = Direktorat::findOrFail($id);
        $old = $model->toArray();
        $model->update(['status' => 'nonaktif']);
        $this->audit->log('nonaktif', 'organisasi', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Data dinonaktifkan.', 'data' => $model]);
    }
}
