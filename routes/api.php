<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PresensiController;
use App\Http\Controllers\Api\PresensiKoreksiController;
use App\Http\Controllers\Api\DinasLuarController;
use App\Http\Controllers\Api\WfhController;
use App\Http\Controllers\Api\WfaController;
use App\Http\Controllers\Api\IzinController;
use App\Http\Controllers\Api\CutiTahunanController;
use App\Http\Controllers\Api\BlockLeaveController;
use App\Http\Controllers\Api\CutiMelahirkanController;
use App\Http\Controllers\Api\CutiBesarController;
use App\Http\Controllers\Api\LemburController;
use App\Http\Controllers\Api\PengumumanController;
use App\Http\Controllers\Api\PayslipController;
use App\Http\Controllers\Api\PayslipAdminController;
use App\Http\Controllers\Api\NotifikasiController;
use App\Http\Controllers\Api\ReportingController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\AuditController;
use App\Http\Controllers\Api\Admin\KonfigurasiController;
use App\Http\Controllers\Api\Organisasi\EntitasController;
use App\Http\Controllers\Api\Organisasi\DirektoratController;
use App\Http\Controllers\Api\Organisasi\DivisiController;
use App\Http\Controllers\Api\Organisasi\DepartemenController;
use App\Http\Controllers\Api\Organisasi\PenempatanController;
use App\Http\Controllers\Api\Organisasi\DelegasiController;
use App\Http\Controllers\Api\Organisasi\ApprovalMatrixController;
use App\Http\Controllers\Api\Master\KantorController;
use App\Http\Controllers\Api\Master\UnitKerjaController;
use App\Http\Controllers\Api\Master\JabatanController;
use App\Http\Controllers\Api\Master\JadwalController;
use App\Http\Controllers\Api\Master\HariLiburController;
use App\Http\Controllers\Api\Master\JenisIzinController;
use App\Http\Controllers\Api\Master\JenisPengumumanController;
use App\Http\Controllers\Api\Master\KomponenGajiController;
use App\Http\Controllers\Api\WebAuthnController;
use App\Http\Controllers\Api\PushController;

// Auth (public)
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle-login');

// WebAuthn public (login) — needs session for challenge
Route::post('/webauthn/login/options', [WebAuthnController::class, 'loginOptions'])->middleware(['web']);
Route::post('/webauthn/login', [WebAuthnController::class, 'login'])->middleware(['web']);

// Protected routes
Route::middleware(['auth:sanctum', 'idle'])->group(function () {
    // Auth
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/change-pin', [AuthController::class, 'changePin']);

    // WebAuthn credential management (needs both sanctum + session for challenge)
    Route::post('/webauthn/register/options', [WebAuthnController::class, 'registerOptions'])->middleware('web');
    Route::post('/webauthn/register', [WebAuthnController::class, 'register'])->middleware('web');
    Route::get('/webauthn/credentials', [WebAuthnController::class, 'credentials']);
    Route::delete('/webauthn/credentials/{id}', [WebAuthnController::class, 'deleteCredential']);

    // Web Push
    Route::get('/push/vapid-key', [PushController::class, 'vapidKey']);
    Route::get('/push/status', [PushController::class, 'status']);
    Route::post('/push/subscribe', [PushController::class, 'subscribe']);
    Route::post('/push/unsubscribe', [PushController::class, 'unsubscribe']);
    Route::post('/push/test', [PushController::class, 'test']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'employee'])->middleware('perm:dashboard,R');
    Route::get('/dashboard/supervisor', [DashboardController::class, 'supervisor'])->middleware('perm:dashboard_supervisor,R');
    Route::get('/dashboard/hr', [DashboardController::class, 'hr'])->middleware('perm:dashboard_hr,R');

    // Master data
    $masters = [
        'kantor' => KantorController::class,
        'unit-kerja' => UnitKerjaController::class,
        'jabatan' => JabatanController::class,
        'jadwal' => JadwalController::class,
        'hari-libur' => HariLiburController::class,
        'jenis-izin' => JenisIzinController::class,
        'jenis-pengumuman' => JenisPengumumanController::class,
        'komponen-gaji' => KomponenGajiController::class,
    ];
    foreach ($masters as $slug => $ctrl) {
        Route::get("/master/{$slug}", [$ctrl, 'index'])->middleware('perm:master_data,R');
        Route::get("/master/{$slug}/{id}", [$ctrl, 'show'])->middleware('perm:master_data,R');
        Route::post("/master/{$slug}", [$ctrl, 'store'])->middleware('perm:master_data,C');
        Route::put("/master/{$slug}/{id}", [$ctrl, 'update'])->middleware('perm:master_data,U');
        Route::delete("/master/{$slug}/{id}", [$ctrl, 'destroy'])->middleware('perm:master_data,D');
    }

    // Organisasi
    Route::apiResource('organisasi/entitas', EntitasController::class)->middleware('perm:organisasi,R');
    Route::apiResource('organisasi/direktorat', DirektoratController::class)->middleware('perm:organisasi,R');
    Route::apiResource('organisasi/divisi', DivisiController::class)->middleware('perm:organisasi,R');
    Route::apiResource('organisasi/departemen', DepartemenController::class)->middleware('perm:organisasi,R');
    Route::apiResource('organisasi/penempatan', PenempatanController::class)->middleware('perm:organisasi,R');
    Route::apiResource('organisasi/delegasi', DelegasiController::class)->middleware('perm:organisasi,R');
    Route::apiResource('organisasi/approval-matrix', ApprovalMatrixController::class)->middleware('perm:organisasi,R');

    // Presensi
    Route::post('/presensi/clock-in', [PresensiController::class, 'clockIn'])->middleware('perm:presensi,C');
    Route::post('/presensi/clock-out', [PresensiController::class, 'clockOut'])->middleware('perm:presensi,C');
    Route::get('/presensi/today', [PresensiController::class, 'today'])->middleware('perm:presensi,R');
    Route::get('/presensi/riwayat', [PresensiController::class, 'riwayat'])->middleware('perm:presensi,R');
    Route::get('/presensi/tim', [PresensiController::class, 'tim'])->middleware('perm:presensi_tim,R');
    Route::post('/presensi/{id}/verify', [PresensiController::class, 'verify'])->middleware('perm:verifikasi,A');

    // Presensi koreksi
    Route::get('/presensi/koreksi', [PresensiKoreksiController::class, 'index'])->middleware('perm:presensi,R');
    Route::post('/presensi/koreksi', [PresensiKoreksiController::class, 'store'])->middleware('perm:presensi,C');
    Route::post('/presensi/koreksi/{id}/approve', [PresensiKoreksiController::class, 'approve'])->middleware('perm:presensi,A');
    Route::post('/presensi/koreksi/{id}/reject', [PresensiKoreksiController::class, 'reject'])->middleware('perm:presensi,A');

    // Dinas Luar, WFH, WFA
    Route::apiResource('dinas-luar', DinasLuarController::class)->middleware('perm:dinas_luar,R');
    Route::post('/dinas-luar/{id}/approve', [DinasLuarController::class, 'approve'])->middleware('perm:dinas_luar,A');
    Route::post('/dinas-luar/{id}/reject', [DinasLuarController::class, 'reject'])->middleware('perm:dinas_luar,A');

    Route::apiResource('wfh', WfhController::class)->middleware('perm:wfh,R');
    Route::post('/wfh/{id}/approve', [WfhController::class, 'approve'])->middleware('perm:wfh,A');
    Route::post('/wfh/{id}/reject', [WfhController::class, 'reject'])->middleware('perm:wfh,A');

    Route::apiResource('wfa', WfaController::class)->middleware('perm:wfa,R');
    Route::post('/wfa/{id}/approve', [WfaController::class, 'approve'])->middleware('perm:wfa,A');
    Route::post('/wfa/{id}/reject', [WfaController::class, 'reject'])->middleware('perm:wfa,A');

    // Izin
    Route::get('/izin-jenis', [IzinController::class, 'jenisIzin'])->middleware('perm:izin,R');
    Route::apiResource('izin', IzinController::class)->middleware('perm:izin,R');
    Route::post('/izin/{id}/approve', [IzinController::class, 'approve'])->middleware('perm:izin,A');
    Route::post('/izin/{id}/reject', [IzinController::class, 'reject'])->middleware('perm:izin,A');
    Route::post('/izin/{id}/cancel', [IzinController::class, 'cancel'])->middleware('perm:izin,U');

    // Cuti Tahunan
    Route::apiResource('cuti-tahunan', CutiTahunanController::class)->middleware('perm:cuti_tahunan,R');
    Route::get('/cuti-tahunan-saldo', [CutiTahunanController::class, 'saldo'])->middleware('perm:cuti_tahunan,R');
    Route::post('/cuti-tahunan/{id}/approve', [CutiTahunanController::class, 'approve'])->middleware('perm:cuti_tahunan,A');
    Route::post('/cuti-tahunan/{id}/reject', [CutiTahunanController::class, 'reject'])->middleware('perm:cuti_tahunan,A');

    // Block Leave
    Route::apiResource('block-leave', BlockLeaveController::class)->middleware('perm:block_leave,R');
    Route::post('/block-leave/{id}/approve', [BlockLeaveController::class, 'approve'])->middleware('perm:block_leave,A');
    Route::post('/block-leave/{id}/reject', [BlockLeaveController::class, 'reject'])->middleware('perm:block_leave,A');

    // Cuti Melahirkan
    Route::apiResource('cuti-melahirkan', CutiMelahirkanController::class)->middleware('perm:cuti_melahirkan,R');
    Route::post('/cuti-melahirkan/{id}/approve', [CutiMelahirkanController::class, 'approve'])->middleware('perm:cuti_melahirkan,A');
    Route::post('/cuti-melahirkan/{id}/reject', [CutiMelahirkanController::class, 'reject'])->middleware('perm:cuti_melahirkan,A');

    // Cuti Besar
    Route::apiResource('cuti-besar', CutiBesarController::class)->middleware('perm:cuti_besar,R');
    Route::post('/cuti-besar/{id}/approve', [CutiBesarController::class, 'approve'])->middleware('perm:cuti_besar,A');
    Route::post('/cuti-besar/{id}/reject', [CutiBesarController::class, 'reject'])->middleware('perm:cuti_besar,A');

    // Lembur
    Route::apiResource('lembur', LemburController::class)->middleware('perm:lembur,R');
    Route::post('/lembur/{id}/approve', [LemburController::class, 'approve'])->middleware('perm:lembur,A');
    Route::post('/lembur/{id}/reject', [LemburController::class, 'reject'])->middleware('perm:lembur,A');
    Route::post('/lembur/{id}/mulai', [LemburController::class, 'mulai'])->middleware('perm:lembur,U');
    Route::post('/lembur/{id}/selesai', [LemburController::class, 'selesai'])->middleware('perm:lembur,U');

    // Pengumuman
    Route::apiResource('pengumuman', PengumumanController::class)->middleware('perm:pengumuman,R');
    Route::post('/pengumuman/{id}/publish', [PengumumanController::class, 'publish'])->middleware('perm:pengumuman,P');
    Route::post('/pengumuman/{id}/retract', [PengumumanController::class, 'retract'])->middleware('perm:pengumuman,U');
    Route::post('/pengumuman/{id}/confirm', [PengumumanController::class, 'confirmRead']);
    Route::get('/pengumuman/{id}/tracking', [PengumumanController::class, 'tracking'])->middleware('perm:pengumuman,R');

    // Payslip (employee)
    Route::get('/payslip', [PayslipController::class, 'index'])->middleware('perm:payslip,R');
    Route::get('/payslip/{id}', [PayslipController::class, 'show'])->middleware('perm:payslip,R');
    Route::get('/payslip/{id}/download', [PayslipController::class, 'download'])->middleware('perm:payslip,R');
    Route::post('/payslip/verify-pin', [PayslipController::class, 'verifyPin'])->middleware('perm:payslip,R');

    // Payslip admin
    Route::get('/payslip-admin/periode', [PayslipAdminController::class, 'periodeList'])->middleware('perm:payslip_admin,R');
    Route::post('/payslip-admin/import', [PayslipAdminController::class, 'import'])->middleware('perm:payslip_admin,C');
    Route::post('/payslip-admin/validate/{periode}', [PayslipAdminController::class, 'validatePeriode'])->middleware('perm:payslip_admin,U');
    Route::post('/payslip-admin/publish/{periode}', [PayslipAdminController::class, 'publishPeriode'])->middleware('perm:payslip_admin,P');
    Route::post('/payslip-admin/retract/{periode}', [PayslipAdminController::class, 'retractPeriode'])->middleware('perm:payslip_admin,U');

    // Notifikasi
    Route::get('/notifikasi', [NotifikasiController::class, 'index']);
    Route::get('/notifikasi/unread-count', [NotifikasiController::class, 'unreadCount']);
    Route::post('/notifikasi/{id}/read', [NotifikasiController::class, 'markRead']);
    Route::post('/notifikasi/read-all', [NotifikasiController::class, 'markAllRead']);

    // Laporan
    Route::get('/laporan/{type}', [ReportingController::class, 'generate'])->middleware('perm:reporting,R');

    // Admin
    Route::apiResource('admin/users', UserController::class)->middleware('perm:administrasi_sistem,R');
    Route::post('/admin/users/{id}/reset-password', [UserController::class, 'resetPassword'])->middleware('perm:administrasi_sistem,U');
    Route::apiResource('admin/roles', RoleController::class)->middleware('perm:administrasi_sistem,R');
    Route::get('/admin/audit-trail', [AuditController::class, 'index'])->middleware('perm:audit_trail,R');
    Route::apiResource('admin/konfigurasi', KonfigurasiController::class)->middleware('perm:konfigurasi,R');
});
