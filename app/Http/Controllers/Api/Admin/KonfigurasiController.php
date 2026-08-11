<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Konfigurasi;
use App\Services\AuditTrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KonfigurasiController extends ApiController
{
    public function __construct(private AuditTrailService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = Konfigurasi::query();

        if ($search = $request->query('q')) {
            $query->where(function ($w) use ($search) {
                $w->where('kunci', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        if ($grup = $request->query('grup')) {
            $query->where('grup', $grup);
        }

        $perPage = min((int) $request->query('per_page', 25), 200);

        return response()->json($query->orderBy('grup')->orderBy('kunci')->paginate($perPage));
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => Konfigurasi::findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kunci' => 'required|string|max:100|unique:m_konfigurasi,kunci',
            'nilai' => 'nullable|string',
            'tipe' => 'nullable|string|max:20',
            'grup' => 'nullable|string|max:50',
            'deskripsi' => 'nullable|string|max:255',
        ]);

        $model = Konfigurasi::create($data);
        $this->audit->log('create', 'konfigurasi', $model->getTable(), $model->id, null, $model->toArray());

        return response()->json(['message' => 'Konfigurasi tersimpan.', 'data' => $model], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = Konfigurasi::findOrFail($id);
        $old = $model->toArray();

        $data = $request->validate([
            'kunci' => 'required|string|max:100|unique:m_konfigurasi,kunci,' . $id,
            'nilai' => 'nullable|string',
            'tipe' => 'nullable|string|max:20',
            'grup' => 'nullable|string|max:50',
            'deskripsi' => 'nullable|string|max:255',
        ]);

        $model->update($data);
        $this->audit->log('update', 'konfigurasi', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Konfigurasi diperbarui.', 'data' => $model]);
    }

    public function destroy(int $id): JsonResponse
    {
        $model = Konfigurasi::findOrFail($id);
        $old = $model->toArray();

        $model->delete();
        $this->audit->log('delete', 'konfigurasi', 'm_konfigurasi', $id, $old, null);

        return response()->json(['message' => 'Konfigurasi dihapus.']);
    }
}
