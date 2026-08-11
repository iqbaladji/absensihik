<?php

namespace App\Http\Controllers\Api;

use App\Models\CutiTahunan;
use App\Models\Izin;
use App\Models\Lembur;
use App\Models\Notifikasi;
use App\Models\PengumumanPenerima;
use App\Models\Presensi;
use App\Models\PresensiKoreksi;
use App\Models\User;
use App\Services\CutiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends ApiController
{
    public function __construct(private CutiService $cutiService) {}

    public function employee(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = now()->toDateString();

        $presensiHariIni = Presensi::where('id_user', $user->id)
            ->whereDate('tanggal', $today)
            ->first();

        $saldoCuti = $this->cutiService->getSaldo($user->id);

        $pengajuanAktif = collect([
            CutiTahunan::where('id_user', $user->id)->where('status', 'menunggu')->count(),
            Izin::where('id_user', $user->id)->where('status', 'menunggu')->count(),
            Lembur::where('id_user', $user->id)->where('status', 'menunggu')->count(),
        ])->sum();

        $pengumumanBelumDibaca = PengumumanPenerima::where('id_user', $user->id)
            ->whereNull('dibaca_pada')
            ->count();

        return $this->ok([
            'presensi_hari_ini' => $presensiHariIni ? [
                'jam_masuk' => $presensiHariIni->jam_masuk,
                'jam_keluar' => $presensiHariIni->jam_keluar,
                'tipe' => $presensiHariIni->tipe,
                'status_masuk' => $presensiHariIni->status_masuk,
            ] : null,
            'saldo_cuti' => [
                'sisa' => $saldoCuti->sisa,
                'terpakai' => $saldoCuti->terpakai,
                'saldo_awal' => $saldoCuti->saldo_awal,
            ],
            'pengajuan_aktif' => $pengajuanAktif,
            'pengumuman_belum_dibaca' => $pengumumanBelumDibaca,
        ]);
    }

    public function supervisor(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = now()->toDateString();
        $bawahanIds = $user->bawahan()->pluck('id');

        $jumlahTim = $bawahanIds->count();

        $pendingApprovals = collect([
            CutiTahunan::whereIn('id_user', $bawahanIds)->where('status', 'menunggu')->count(),
            Izin::whereIn('id_user', $bawahanIds)->where('status', 'menunggu')->count(),
            Lembur::whereIn('id_user', $bawahanIds)->where('status', 'menunggu')->count(),
            PresensiKoreksi::whereIn('id_user', $bawahanIds)->where('status', 'menunggu')->count(),
        ])->sum();

        $presensiTimHariIni = Presensi::whereIn('id_user', $bawahanIds)
            ->whereDate('tanggal', $today)
            ->count();

        return $this->ok([
            'jumlah_tim' => $jumlahTim,
            'pending_approvals' => $pendingApprovals,
            'presensi_tim_hari_ini' => [
                'hadir' => $presensiTimHariIni,
                'belum_hadir' => $jumlahTim - $presensiTimHariIni,
            ],
        ]);
    }

    public function hr(Request $request): JsonResponse
    {
        $today = now()->toDateString();
        $kantorId = $this->scopeKantorId($request);

        $queryUsers = User::where('status', 'aktif');
        if ($kantorId) {
            $queryUsers->where('id_kantor', $kantorId);
        }
        $totalPegawai = $queryUsers->count();

        $queryPresensi = Presensi::where('tanggal', $today);
        if ($kantorId) {
            $queryPresensi->where('id_kantor', $kantorId);
        }
        $hadirHariIni = $queryPresensi->count();

        $tingkatKehadiran = $totalPegawai > 0 ? round(($hadirHariIni / $totalPegawai) * 100, 1) : 0;

        $queryVerifikasi = Presensi::where('perlu_verifikasi', true);
        if ($kantorId) {
            $queryVerifikasi->where('id_kantor', $kantorId);
        }
        $pendingVerifikasi = $queryVerifikasi->count();

        $cutiAktif = CutiTahunan::where('status', 'disetujui')
            ->where('tanggal_mulai', '<=', $today)
            ->where('tanggal_selesai', '>=', $today);
        if ($kantorId) {
            $cutiAktif->whereHas('user', fn ($q) => $q->where('id_kantor', $kantorId));
        }

        $cutiMenunggu = CutiTahunan::where('status', 'menunggu');
        if ($kantorId) {
            $cutiMenunggu->whereHas('user', fn ($q) => $q->where('id_kantor', $kantorId));
        }

        return $this->ok([
            'total_pegawai' => $totalPegawai,
            'hadir_hari_ini' => $hadirHariIni,
            'tingkat_kehadiran' => $tingkatKehadiran,
            'pending_verifikasi' => $pendingVerifikasi,
            'cuti' => [
                'sedang_cuti' => $cutiAktif->count(),
                'menunggu_persetujuan' => $cutiMenunggu->count(),
            ],
        ]);
    }
}
