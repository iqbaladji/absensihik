<?php

namespace App\Http\Controllers\Api\Organisasi;

use App\Http\Controllers\Api\ApiController;
use App\Models\Delegasi;
use App\Services\AuditTrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DelegasiController extends ApiController
{
    public function __construct(private AuditTrailService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = Delegasi::with(['dari', 'kepada']);

        if ($search = $request->query('q')) {
            $query->where(function ($w) use ($search) {
                $w->whereHas('dari', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('kepada', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
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
            'data' => Delegasi::with(['dari', 'kepada'])->findOrFail($id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_dari' => 'required|integer|exists:users,id',
            'id_kepada' => 'required|integer|exists:users,id|different:id_dari',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'modul' => 'nullable|string|max:50',
            'alasan' => 'nullable|string|max:255',
            'is_aktif' => 'sometimes|boolean',
        ]);

        $model = Delegasi::create($data);
        $this->audit->log('create', 'organisasi', $model->getTable(), $model->id, null, $model->toArray());

        return response()->json([
            'message' => 'Delegasi tersimpan.',
            'data' => $model->load(['dari', 'kepada']),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = Delegasi::findOrFail($id);
        $old = $model->toArray();

        $data = $request->validate([
            'id_dari' => 'required|integer|exists:users,id',
            'id_kepada' => 'required|integer|exists:users,id|different:id_dari',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'modul' => 'nullable|string|max:50',
            'alasan' => 'nullable|string|max:255',
            'is_aktif' => 'sometimes|boolean',
        ]);

        $model->update($data);
        $this->audit->log('update', 'organisasi', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json([
            'message' => 'Delegasi diperbarui.',
            'data' => $model->load(['dari', 'kepada']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $model = Delegasi::findOrFail($id);
        $old = $model->toArray();
        $model->update(['is_aktif' => false]);
        $this->audit->log('nonaktif', 'organisasi', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Delegasi dinonaktifkan.', 'data' => $model]);
    }
}
