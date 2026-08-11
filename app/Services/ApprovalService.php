<?php

namespace App\Services;

use App\Models\ApprovalLog;
use App\Models\ApprovalMatrix;
use App\Models\Delegasi;
use App\Models\User;
use Carbon\Carbon;

class ApprovalService
{
    /**
     * Resolve the approval chain for a given module and requesting user.
     */
    public function resolveChain(string $modul, User $pemohon): array
    {
        $matrices = ApprovalMatrix::where('modul', $modul)
            ->where('is_aktif', true)
            ->where(function ($q) use ($pemohon) {
                $q->whereNull('id_jabatan_pemohon')
                    ->orWhere('id_jabatan_pemohon', $pemohon->id_jabatan);
            })
            ->where(function ($q) use ($pemohon) {
                $q->whereNull('id_unit')
                    ->orWhere('id_unit', $pemohon->id_unit);
            })
            ->orderBy('urutan')
            ->get();

        $chain = [];

        foreach ($matrices as $matrix) {
            $approver = $this->resolveApprover($matrix, $pemohon);
            if (! $approver) {
                continue;
            }

            // Check for active delegation
            $delegasi = $this->findDelegation($approver, $modul);

            $chain[] = [
                'urutan' => $matrix->urutan,
                'id_approver' => $delegasi ? $delegasi->id_kepada : $approver->id,
                'nama_approver' => $delegasi
                    ? User::find($delegasi->id_kepada)?->name
                    : $approver->name,
                'id_delegasi_dari' => $delegasi ? $approver->id : null,
                'tipe' => $matrix->tipe_approver,
            ];
        }

        // Fallback: direct supervisor if no matrix rules match
        if (empty($chain) && $pemohon->id_atasan) {
            $atasan = $pemohon->atasan;
            if ($atasan) {
                $delegasi = $this->findDelegation($atasan, $modul);
                $chain[] = [
                    'urutan' => 1,
                    'id_approver' => $delegasi ? $delegasi->id_kepada : $atasan->id,
                    'nama_approver' => $delegasi
                        ? User::find($delegasi->id_kepada)?->name
                        : $atasan->name,
                    'id_delegasi_dari' => $delegasi ? $atasan->id : null,
                    'tipe' => 'atasan_langsung',
                ];
            }
        }

        return $chain;
    }

    /**
     * Create approval snapshot JSON for a request.
     */
    public function createSnapshot(string $modul, User $pemohon): array
    {
        return [
            'modul' => $modul,
            'pemohon' => [
                'id' => $pemohon->id,
                'nama' => $pemohon->name,
                'jabatan' => $pemohon->jabatan?->nama,
                'unit' => $pemohon->unit?->nama,
            ],
            'chain' => $this->resolveChain($modul, $pemohon),
            'created_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Log an approval action.
     */
    public function logApproval(
        string $refTabel,
        int $idRef,
        User $approver,
        string $aksi,
        ?string $catatan = null,
        int $urutan = 1,
        ?int $idDelegasiDari = null,
    ): ApprovalLog {
        return ApprovalLog::create([
            'ref_tabel' => $refTabel,
            'id_ref' => $idRef,
            'id_approver' => $approver->id,
            'id_delegasi_dari' => $idDelegasiDari,
            'urutan' => $urutan,
            'aksi' => $aksi,
            'catatan' => $catatan,
            'waktu' => now(),
            'created_at' => now(),
        ]);
    }

    /**
     * Check if an approval request has exceeded SLA (default 3 working days).
     */
    public function isOverSla(Carbon $submittedAt, int $slaHours = 72): bool
    {
        return $submittedAt->diffInHours(now()) > $slaHours;
    }

    private function resolveApprover(ApprovalMatrix $matrix, User $pemohon): ?User
    {
        if ($matrix->id_user_approver) {
            return User::find($matrix->id_user_approver);
        }

        if ($matrix->tipe_approver === 'atasan_langsung') {
            return $pemohon->atasan;
        }

        if ($matrix->id_jabatan_approver) {
            return User::where('id_jabatan', $matrix->id_jabatan_approver)
                ->where('status', 'aktif')
                ->when($pemohon->id_kantor, fn ($q) => $q->where('id_kantor', $pemohon->id_kantor))
                ->first();
        }

        return null;
    }

    private function findDelegation(User $approver, string $modul): ?Delegasi
    {
        $today = now()->toDateString();

        return Delegasi::where('id_dari', $approver->id)
            ->where('is_aktif', true)
            ->where('tanggal_mulai', '<=', $today)
            ->where('tanggal_selesai', '>=', $today)
            ->where(function ($q) use ($modul) {
                $q->whereNull('modul')->orWhere('modul', $modul);
            })
            ->first();
    }
}
