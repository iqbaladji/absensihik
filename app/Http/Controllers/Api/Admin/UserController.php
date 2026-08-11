<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\User;
use App\Services\AuditTrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends ApiController
{
    public function __construct(private AuditTrailService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = User::with(['role:id,slug,nama', 'kantor:id,kode,nama', 'unit:id,nama', 'jabatan:id,nama']);

        if ($search = $request->query('q')) {
            $query->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($kantorId = $this->scopeKantorId($request)) {
            $query->where('id_kantor', $kantorId);
        }

        $perPage = min((int) $request->query('per_page', 25), 200);

        return response()->json($query->latest('id')->paginate($perPage));
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with(['role', 'kantor', 'unit', 'jabatan', 'atasan:id,name'])
            ->findOrFail($id);

        return response()->json(['data' => $user]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'nullable|email|max:100|unique:users,email',
            'nip' => 'nullable|string|max:30|unique:users,nip',
            'password' => 'required|string|min:8',
            'id_role' => 'required|integer|exists:m_role,id',
            'id_kantor' => 'nullable|integer|exists:m_kantor,id',
            'id_unit' => 'nullable|integer|exists:m_unit_kerja,id',
            'id_jabatan' => 'nullable|integer|exists:m_jabatan,id',
            'id_atasan' => 'nullable|integer|exists:users,id',
            'id_jadwal' => 'nullable|integer|exists:m_jadwal,id',
            'status' => 'sometimes|in:aktif,nonaktif',
        ]);

        $data['password'] = Hash::make($data['password']);
        $model = User::create($data);

        $this->audit->log('create', 'administrasi_sistem', 'users', $model->id, null, $model->makeHidden(['password'])->toArray());

        return response()->json([
            'message' => 'User tersimpan.',
            'data' => $model->load(['role:id,slug,nama', 'kantor:id,kode,nama', 'unit:id,nama', 'jabatan:id,nama']),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = User::findOrFail($id);
        $old = $model->makeHidden(['password'])->toArray();

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username,' . $id,
            'email' => 'nullable|email|max:100|unique:users,email,' . $id,
            'nip' => 'nullable|string|max:30|unique:users,nip,' . $id,
            'id_role' => 'required|integer|exists:m_role,id',
            'id_kantor' => 'nullable|integer|exists:m_kantor,id',
            'id_unit' => 'nullable|integer|exists:m_unit_kerja,id',
            'id_jabatan' => 'nullable|integer|exists:m_jabatan,id',
            'id_atasan' => 'nullable|integer|exists:users,id',
            'id_jadwal' => 'nullable|integer|exists:m_jadwal,id',
            'status' => 'sometimes|in:aktif,nonaktif',
        ]);

        $model->update($data);
        $this->audit->log('update', 'administrasi_sistem', 'users', $model->id, $old, $model->makeHidden(['password'])->toArray());

        return response()->json([
            'message' => 'User diperbarui.',
            'data' => $model->load(['role:id,slug,nama', 'kantor:id,kode,nama', 'unit:id,nama', 'jabatan:id,nama']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $model = User::findOrFail($id);
        $old = $model->makeHidden(['password'])->toArray();

        $model->update(['status' => 'nonaktif']);
        $this->audit->log('nonaktif', 'administrasi_sistem', 'users', $model->id, $old, $model->makeHidden(['password'])->toArray());

        return response()->json(['message' => 'User dinonaktifkan.', 'data' => $model]);
    }

    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $model = User::findOrFail($id);
        $newPassword = Str::random(12);

        $model->update(['password' => Hash::make($newPassword)]);
        $this->audit->log('reset_password', 'administrasi_sistem', 'users', $model->id, null, null);

        return response()->json([
            'message' => 'Password berhasil direset.',
            'data' => ['password_baru' => $newPassword],
        ]);
    }
}
