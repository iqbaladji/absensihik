<?php

namespace App\Services;

use App\Models\KalenderKerja;
use App\Models\SaldoCuti;
use Carbon\Carbon;

class CutiService
{
    /**
     * Count working days between two dates using m_kalender_kerja.
     */
    public function countWorkingDays(Carbon $start, Carbon $end): int
    {
        $count = KalenderKerja::where('is_hari_kerja', true)
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->count();

        if ($count === 0 && !KalenderKerja::whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])->exists()) {
            $date = $start->copy();
            while ($date->lte($end)) {
                if ($date->isWeekday()) {
                    $count++;
                }
                $date->addDay();
            }
        }

        return $count;
    }

    /**
     * Get or create the leave balance for a user in a given year.
     */
    public function getSaldo(int $idUser, ?int $tahun = null): SaldoCuti
    {
        $tahun = $tahun ?? now()->year;

        return SaldoCuti::firstOrCreate(
            ['id_user' => $idUser, 'tahun' => $tahun],
            ['saldo_awal' => 12, 'terpakai' => 0, 'penyesuaian' => 0, 'sisa' => 12],
        );
    }

    /**
     * Check if user has sufficient balance.
     */
    public function hasSufficientBalance(int $idUser, int $hariDiminta, ?int $tahun = null): bool
    {
        $saldo = $this->getSaldo($idUser, $tahun);

        return $saldo->sisa >= $hariDiminta;
    }

    /**
     * Deduct from the user's leave balance.
     */
    public function deductBalance(int $idUser, int $jumlahHari, ?int $tahun = null): SaldoCuti
    {
        $saldo = $this->getSaldo($idUser, $tahun);
        $saldo->terpakai += $jumlahHari;
        $saldo->sisa = $saldo->saldo_awal - $saldo->terpakai + $saldo->penyesuaian;
        $saldo->save();

        return $saldo;
    }

    /**
     * Restore leave balance (e.g. when a request is rejected/cancelled).
     */
    public function restoreBalance(int $idUser, int $jumlahHari, ?int $tahun = null): SaldoCuti
    {
        $saldo = $this->getSaldo($idUser, $tahun);
        $saldo->terpakai = max(0, $saldo->terpakai - $jumlahHari);
        $saldo->sisa = $saldo->saldo_awal - $saldo->terpakai + $saldo->penyesuaian;
        $saldo->save();

        return $saldo;
    }

    public function addWorkingDays(Carbon $start, int $days): Carbon
    {
        $date = $start->copy();
        $added = 0;
        while ($added < $days) {
            $date->addDay();
            if ($date->isWeekday()) {
                $added++;
            }
        }
        return $date;
    }

    /**
     * Validate block leave: must be exactly 5 consecutive working days.
     * Non-working days in between don't count and don't break the sequence.
     */
    public function validateBlockLeave(Carbon $start, Carbon $end): array
    {
        $workingDays = $this->countWorkingDays($start, $end);

        if ($workingDays !== 5) {
            return [
                'valid' => false,
                'message' => "Block leave harus tepat 5 hari kerja. Ditemukan {$workingDays} hari kerja.",
                'jumlah_hari_kerja' => $workingDays,
            ];
        }

        $hasCalendar = KalenderKerja::whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])->exists();
        if ($hasCalendar) {
            $dates = KalenderKerja::where('is_hari_kerja', true)
                ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
                ->orderBy('tanggal')
                ->pluck('tanggal');

            if ($dates->count() !== 5) {
                return [
                    'valid' => false,
                    'message' => 'Block leave harus tepat 5 hari kerja berurutan.',
                    'jumlah_hari_kerja' => $dates->count(),
                ];
            }
        }

        return [
            'valid' => true,
            'message' => 'Block leave valid.',
            'jumlah_hari_kerja' => 5,
        ];
    }
}
