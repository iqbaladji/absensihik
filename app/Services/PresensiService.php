<?php

namespace App\Services;

use App\Models\Kantor;
use App\Models\Shift;
use Carbon\Carbon;

class PresensiService
{
    /**
     * Haversine distance between two GPS points in metres.
     */
    public function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check whether user position is within office radius.
     * Returns [within => bool, distance => float, perlu_verifikasi => bool].
     */
    public function checkRadius(
        float $lat,
        float $lng,
        float $accuracy,
        Kantor $kantor,
    ): array {
        $distance = $this->haversineDistance($lat, $lng, (float) $kantor->latitude, (float) $kantor->longitude);
        $radius = $kantor->radius;

        $within = $distance <= $radius;

        // Border case: GPS accuracy overlaps the radius boundary
        $perluVerifikasi = false;
        if (! $within && $accuracy > 0) {
            $borderDistance = abs($distance - $radius);
            if ($accuracy >= $borderDistance) {
                $perluVerifikasi = true;
            }
        }

        return [
            'within' => $within,
            'distance' => round($distance, 2),
            'perlu_verifikasi' => $perluVerifikasi,
        ];
    }

    /**
     * Determine clock-in status based on shift schedule.
     */
    public function determineStatusMasuk(Carbon $jamMasuk, ?Shift $shift): string
    {
        if (! $shift || $shift->is_libur) {
            return 'hadir';
        }

        $batasJamMasuk = Carbon::parse($shift->jam_masuk)->addMinutes($shift->toleransi_terlambat);

        if ($jamMasuk->format('H:i:s') <= $batasJamMasuk->format('H:i:s')) {
            return 'tepat_waktu';
        }

        return 'terlambat';
    }

    /**
     * Determine clock-out status based on shift schedule.
     */
    public function determineStatusKeluar(Carbon $jamKeluar, ?Shift $shift): string
    {
        if (! $shift || $shift->is_libur) {
            return 'hadir';
        }

        $batasJamKeluar = Carbon::parse($shift->jam_keluar)->subMinutes($shift->toleransi_pulang_awal);

        if ($jamKeluar->format('H:i:s') >= $batasJamKeluar->format('H:i:s')) {
            return 'tepat_waktu';
        }

        return 'pulang_awal';
    }

    /**
     * Get the applicable shift for a user on a given day.
     */
    public function getShiftForDay(int $idJadwal, Carbon $date): ?Shift
    {
        $dayName = strtolower($date->locale('id')->dayName);

        return Shift::where('id_jadwal', $idJadwal)
            ->where('hari', $dayName)
            ->first();
    }
}
