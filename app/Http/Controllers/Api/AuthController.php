<?php

namespace App\Http\Controllers\Api;

use App\Models\LoginAttempt;
use App\Models\User;
use App\Services\AuditTrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends ApiController
{
    public function __construct(private AuditTrailService $audit) {}

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        $berhasil = $user && Hash::check($request->password, $user->password);

        LoginAttempt::create([
            'username' => $request->username,
            'ip' => $request->ip(),
            'berhasil' => $berhasil,
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'created_at' => now(),
        ]);

        if (! $berhasil) {
            return response()->json(['message' => 'Username atau password salah.'], 401);
        }

        if (! $user->isActive()) {
            return response()->json(['message' => 'Akun tidak aktif. Hubungi administrator.'], 403);
        }

        $token = $user->createToken('api')->plainTextToken;
        $this->audit->log('login', 'auth', 'users', $user->id, null, null);

        return response()->json([
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $token,
                'user' => $this->profileData($user),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->ok($this->profileData($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->user()->currentAccessToken()->delete();
        $this->audit->log('logout', 'auth', 'users', $user->id, null, null);

        return $this->ok(message: 'Logout berhasil.');
    }

    public function changePin(Request $request): JsonResponse
    {
        $request->validate([
            'pin_lama' => 'required|string',
            'pin_baru' => 'required|string|digits:6|confirmed',
        ]);

        $user = $request->user();

        if ($user->pin_payslip && ! Hash::check($request->pin_lama, $user->pin_payslip)) {
            return response()->json(['message' => 'PIN lama salah.'], 422);
        }

        $user->update(['pin_payslip' => Hash::make($request->pin_baru)]);
        $this->audit->log('change_pin', 'auth', 'users', $user->id, null, null);

        return $this->ok(message: 'PIN berhasil diubah.');
    }

    private function profileData(User $user): array
    {
        $user->load(['role', 'kantor', 'unit', 'jabatan', 'atasan']);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'nip' => $user->nip,
            'status' => $user->status,
            'role' => $user->role ? [
                'slug' => $user->role->slug,
                'nama' => $user->role->nama,
                'hak_akses' => $user->role->hak_akses,
            ] : null,
            'kantor' => $user->kantor ? [
                'id' => $user->kantor->id,
                'kode' => $user->kantor->kode,
                'nama' => $user->kantor->nama,
            ] : null,
            'unit' => $user->unit ? [
                'id' => $user->unit->id,
                'nama' => $user->unit->nama,
            ] : null,
            'jabatan' => $user->jabatan ? [
                'id' => $user->jabatan->id,
                'nama' => $user->jabatan->nama,
            ] : null,
            'atasan' => $user->atasan ? [
                'id' => $user->atasan->id,
                'name' => $user->atasan->name,
            ] : null,
        ];
    }
}
