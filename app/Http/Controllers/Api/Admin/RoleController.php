<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Role;
use App\Services\AuditTrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends ApiController
{
    public function __construct(private AuditTrailService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = Role::withCount('users');

        if ($search = $request->query('q')) {
            $query->where(function ($w) use ($search) {
                $w->where('slug', 'like', "%{$search}%")
                    ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->query('per_page', 25), 200);

        return response()->json($query->latest('id')->paginate($perPage));
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => Role::withCount('users')->findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug' => 'required|string|max:50|unique:m_role,slug',
            'nama' => 'required|string|max:100',
            'deskripsi' => 'nullable|string|max:255',
            'hak_akses' => 'required|array',
        ]);

        $model = Role::create($data);
        $this->audit->log('create', 'administrasi_sistem', $model->getTable(), $model->id, null, $model->toArray());

        return response()->json(['message' => 'Role tersimpan.', 'data' => $model], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = Role::findOrFail($id);

        if ($model->is_system) {
            return response()->json(['message' => 'Role sistem tidak dapat diubah.'], 422);
        }

        $old = $model->toArray();
        $data = $request->validate([
            'slug' => 'required|string|max:50|unique:m_role,slug,' . $id,
            'nama' => 'required|string|max:100',
            'deskripsi' => 'nullable|string|max:255',
            'hak_akses' => 'required|array',
        ]);

        $model->update($data);
        $this->audit->log('update', 'administrasi_sistem', $model->getTable(), $model->id, $old, $model->toArray());

        return response()->json(['message' => 'Role diperbarui.', 'data' => $model]);
    }

    public function destroy(int $id): JsonResponse
    {
        $model = Role::findOrFail($id);

        if ($model->is_system) {
            return response()->json(['message' => 'Role sistem tidak dapat dihapus.'], 422);
        }

        if ($model->users()->exists()) {
            return response()->json(['message' => 'Role masih digunakan oleh user.'], 422);
        }

        $old = $model->toArray();
        $model->delete();
        $this->audit->log('delete', 'administrasi_sistem', 'm_role', $id, $old, null);

        return response()->json(['message' => 'Role dihapus.']);
    }
}
