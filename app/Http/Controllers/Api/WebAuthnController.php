<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laragear\WebAuthn\Assertion\Validator\AssertionValidation;
use Laragear\WebAuthn\Assertion\Validator\AssertionValidator;
use Laragear\WebAuthn\Http\Requests\AssertedRequest;
use Laragear\WebAuthn\Http\Requests\AssertionRequest;
use Laragear\WebAuthn\Http\Requests\AttestationRequest;
use Laragear\WebAuthn\Http\Requests\AttestedRequest;
use Laragear\WebAuthn\JsonTransport;
use Laragear\WebAuthn\Models\WebAuthnCredential;

class WebAuthnController extends ApiController
{
    // --- Registration (attestation) ---

    public function registerOptions(AttestationRequest $request)
    {
        return $request
            ->fastRegistration()
            ->toCreate();
    }

    public function register(AttestedRequest $request): JsonResponse
    {
        $ua = substr($request->userAgent() ?? 'Perangkat', 0, 60);

        $request->save([
            'alias' => $ua,
        ]);

        return response()->json(['message' => 'Biometrik berhasil diaktifkan.']);
    }

    public function credentials(Request $request): JsonResponse
    {
        $user = $request->user();
        $rows = WebAuthnCredential::where('authenticatable_id', $user->id)
            ->where('authenticatable_type', User::class)
            ->orderByDesc('created_at')
            ->get(['id', 'alias', 'created_at', 'disabled_at']);

        return response()->json(['data' => $rows]);
    }

    public function deleteCredential(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $cred = WebAuthnCredential::where('id', $id)
            ->where('authenticatable_id', $user->id)
            ->where('authenticatable_type', User::class)
            ->firstOrFail();
        $cred->delete();

        return response()->json(['message' => 'Perangkat dihapus.']);
    }

    // --- Login (assertion) ---

    public function loginOptions(Request $request, AssertionRequest $assertion)
    {
        $data = $request->validate(['username' => 'nullable|string']);

        $credentials = isset($data['username']) ? ['username' => $data['username']] : null;

        return $assertion->toVerify($credentials);
    }

    public function login(AssertedRequest $request): JsonResponse
    {
        // Validate assertion manually against session-stored challenge.
        // AssertedRequest already ran passedValidation → data validated for shape;
        // AssertionValidator will verify against the challenge from session.
        $validation = app(AssertionValidator::class)
            ->send(new AssertionValidation(new JsonTransport($request->validated())))
            ->thenReturn();

        $credential = $validation->credential;
        if (! $credential) {
            return response()->json(['message' => 'Verifikasi biometrik gagal.'], 422);
        }

        $user = User::find($credential->authenticatable_id);
        if (! $user || $user->status !== 'aktif') {
            return response()->json(['message' => 'Akun tidak aktif.'], 403);
        }

        // Update credential counter (already handled by validator via events? ensure save)
        $credential->save();

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $token,
                'user' => $this->userPayload($user),
            ],
        ]);
    }

    private function userPayload(User $user): array
    {
        $user->load(['role', 'kantor', 'unit', 'jabatan', 'atasan']);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'nip' => $user->nip,
            'status' => $user->status,
            'has_pin' => (bool) $user->pin_payslip,
            'role' => $user->role ? [
                'slug' => $user->role->slug,
                'nama' => $user->role->nama,
                'hak_akses' => $user->role->hak_akses,
            ] : null,
            'kantor' => $user->kantor ? ['id' => $user->kantor->id, 'kode' => $user->kantor->kode, 'nama' => $user->kantor->nama] : null,
            'unit' => $user->unit ? ['id' => $user->unit->id, 'kode' => $user->unit->kode, 'nama' => $user->unit->nama] : null,
            'jabatan' => $user->jabatan ? ['id' => $user->jabatan->id, 'kode' => $user->jabatan->kode, 'nama' => $user->jabatan->nama] : null,
            'atasan' => $user->atasan ? ['id' => $user->atasan->id, 'name' => $user->atasan->name] : null,
        ];
    }
}
