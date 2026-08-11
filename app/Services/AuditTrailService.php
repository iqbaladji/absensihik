<?php

namespace App\Services;

use App\Models\AuditTrail;
use Illuminate\Support\Facades\Auth;

class AuditTrailService
{
    public function log(
        string $aksi,
        string $modul,
        ?string $refTabel = null,
        int|string|null $idRef = null,
        ?array $dataLama = null,
        ?array $dataBaru = null,
    ): AuditTrail {
        $user = Auth::user();
        $request = request();

        return AuditTrail::create([
            'id_user' => $user?->id,
            'username' => $user?->username,
            'aksi' => $aksi,
            'modul' => $modul,
            'ref_tabel' => $refTabel,
            'id_ref' => $idRef !== null ? (string) $idRef : null,
            'data_lama' => $dataLama,
            'data_baru' => $dataBaru,
            'ip' => $request?->ip(),
            'user_agent' => substr((string) $request?->userAgent(), 0, 255),
            'waktu' => now(),
            'created_at' => now(),
        ]);
    }
}
