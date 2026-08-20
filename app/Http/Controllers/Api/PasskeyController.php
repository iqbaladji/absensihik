<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Actions\DeletePasskey;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Http\Requests\PasskeyRegistrationRequest;
use Laravel\Passkeys\Http\Requests\PasskeyVerificationRequest;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Support\WebAuthn;

class PasskeyController extends ApiController
{
    // --- Registration (attestation) ---

    public function registerOptions(Request $request, GenerateRegistrationOptions $generate): JsonResponse
    {
        $user = $request->user();
        $options = $generate($user);

        $request->session()->put('passkey.registration_options', WebAuthn::toJson($options));

        return response()->json(WebAuthn::toBrowserArray($options));
    }

    public function register(PasskeyRegistrationRequest $request, StorePasskey $store): JsonResponse
    {
        $user = $request->user();

        $name = substr($request->userAgent() ?? 'Perangkat', 0, 60);

        $passkey = $store(
            $user,
            $name,
            $request->credential(),
            $request->registrationOptions(),
        );

        return response()->json([
            'message' => 'Biometrik berhasil diaktifkan.',
            'passkey' => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'created_at' => $passkey->created_at,
            ],
        ]);
    }

    public function credentials(Request $request): JsonResponse
    {
        $user = $request->user();
        $rows = $user->passkeys()
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'created_at', 'last_used_at']);

        return response()->json(['data' => $rows]);
    }

    public function deleteCredential(Request $request, int $id, DeletePasskey $delete): JsonResponse
    {
        $user = $request->user();
        $passkey = $user->passkeys()->where('id', $id)->firstOrFail();
        $delete($user, $passkey);

        return response()->json(['message' => 'Perangkat dihapus.']);
    }

    // --- Login (assertion) ---

    public function loginOptions(Request $request, GenerateVerificationOptions $generate): JsonResponse
    {
        // Username-driven flow: kalau ada username, sertakan allowCredentials milik user itu.
        $request->validate(['username' => 'nullable|string']);
        $user = null;
        if ($username = $request->input('username')) {
            $user = User::where('username', $username)->first();
        }

        $options = $generate($user);

        $request->session()->put('passkey.verification_options', WebAuthn::toJson($options));

        return response()->json(WebAuthn::toBrowserArray($options));
    }

    public function login(PasskeyVerificationRequest $request, VerifyPasskey $verify): JsonResponse
    {
        try {
            $passkey = $verify(
                $request->credential(),
                $request->verificationOptions(),
            );
        } catch (InvalidPasskeyException) {
            throw ValidationException::withMessages([
                'credential' => 'Verifikasi biometrik gagal.',
            ]);
        }

        /** @var User $user */
        $user = $passkey->user;

        if ($user->status !== 'aktif') {
            return response()->json(['message' => 'Akun tidak aktif.'], 403);
        }

        $passkey->forceFill(['last_used_at' => now()])->save();

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
