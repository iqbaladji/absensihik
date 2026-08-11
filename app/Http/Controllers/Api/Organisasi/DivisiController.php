<?php

namespace App\Http\Controllers\Api\Organisasi;

use App\Http\Controllers\Api\ApiController;
use App\Models\Divisi;
use App\Services\AuditTrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DivisiController extends ApiController
{
    public function __construct(private AuditTrailService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = Divisi::with('direktorat');

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
        return response()->json(['data' => Divisi::with('direktorat')->findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_direktorat' => 'required|integer|exists:m_direktorat,id',
            'kode' => 'required|string|max:20|unique:m_divisi,kode',
            'nama' => 'required|string|max:100',
            'status' => 'sometimes|in:aktif,nonaktif',
        ]);

        $model = Divisi::create($data);
        $this->audit->log('create', 'organisasi', $model->getTable(), $model->id, null, $model->toArray());

        return response()->json(['message' => 'Data tersimpan.', 'data' => $model->load('direktorat')], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = Divisi::findOrFail($id);
        $old = $model->toArray();

        $data = $request->validate([
            'id_direktorat' => 'required|integer|exists:m_direktorat,id',
            'kode' => 'required|string|max:20|unique:m_divisi,kode,' . $id,
            'nama' => 'required|string|max:100',
            'status' => 'sometimes|in:aktif,nonaktif',
        ]);

        $model->update($data);
        $this->audit->log('update', 'organisasi', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Data diperbarui.', 'data' => $model->load('direktorat')]);
    }

    public function destroy(int $id): JsonResponse
    {
        $model = Divisi::findOrFail($id);
        $old = $model->toArray();
        $model->update(['status' => 'nonaktif']);
        $this->audit->log('nonaktif', 'organisasi', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Data dinonaktifkan.', 'data' => $model]);
    }
}
