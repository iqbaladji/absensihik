import { createRouter, createWebHistory } from 'vue-router';
import { useAuth } from '../stores/auth';

const routes = [
    { path: '/login', name: 'login', component: () => import('../pages/Login.vue'), meta: { public: true } },

    { path: '/', name: 'dashboard', component: () => import('../pages/dashboard/DashboardEmployee.vue'), meta: { modul: 'dashboard' } },
    { path: '/profil', name: 'profil', component: () => import('../pages/Profil.vue') },
    { path: '/ubah-pin', name: 'ubah-pin', component: () => import('../pages/UbahPin.vue') },
    { path: '/cuti', name: 'cuti-hub', component: () => import('../pages/cuti/CutiHub.vue') },
    { path: '/dashboard/supervisor', name: 'dashboard-supervisor', component: () => import('../pages/dashboard/DashboardSupervisor.vue'), meta: { modul: 'dashboard_supervisor' } },
    { path: '/dashboard/hr', name: 'dashboard-hr', component: () => import('../pages/dashboard/DashboardHr.vue'), meta: { modul: 'dashboard_hr' } },

    { path: '/presensi', name: 'presensi', component: () => import('../pages/presensi/PresensiClock.vue'), meta: { modul: 'presensi' } },
    { path: '/presensi/riwayat', name: 'presensi-riwayat', component: () => import('../pages/presensi/PresensiRiwayat.vue'), meta: { modul: 'presensi' } },
    { path: '/presensi/tim', name: 'presensi-tim', component: () => import('../pages/presensi/PresensiTim.vue'), meta: { modul: 'presensi_tim' } },
    { path: '/presensi/verifikasi', name: 'presensi-verifikasi', component: () => import('../pages/presensi/PresensiVerifikasi.vue'), meta: { modul: 'verifikasi' } },
    { path: '/presensi/koreksi', name: 'presensi-koreksi', component: () => import('../pages/presensi/PresensiKoreksi.vue'), meta: { modul: 'presensi' } },

    { path: '/dinas-luar', name: 'dinas-luar', component: () => import('../pages/dinas/DinasLuarList.vue'), meta: { modul: 'dinas_luar' } },
    { path: '/wfh', name: 'wfh', component: () => import('../pages/dinas/WfhList.vue'), meta: { modul: 'wfh' } },
    { path: '/wfa', name: 'wfa', component: () => import('../pages/dinas/WfaList.vue'), meta: { modul: 'wfa' } },

    { path: '/izin', name: 'izin', component: () => import('../pages/izin/IzinList.vue'), meta: { modul: 'izin' } },
    { path: '/cuti-tahunan', name: 'cuti-tahunan', component: () => import('../pages/cuti/CutiTahunanList.vue'), meta: { modul: 'cuti_tahunan' } },
    { path: '/block-leave', name: 'block-leave', component: () => import('../pages/cuti/BlockLeaveList.vue'), meta: { modul: 'block_leave' } },
    { path: '/cuti-melahirkan', name: 'cuti-melahirkan', component: () => import('../pages/cuti/CutiMelahirkanList.vue'), meta: { modul: 'cuti_melahirkan' } },
    { path: '/cuti-besar', name: 'cuti-besar', component: () => import('../pages/cuti/CutiBesarList.vue'), meta: { modul: 'cuti_besar' } },
    { path: '/saldo-cuti', name: 'saldo-cuti', component: () => import('../pages/cuti/SaldoCutiView.vue'), meta: { modul: 'cuti_tahunan' } },

    { path: '/lembur', name: 'lembur', component: () => import('../pages/lembur/LemburList.vue'), meta: { modul: 'lembur' } },

    { path: '/pengumuman', name: 'pengumuman', component: () => import('../pages/pengumuman/PengumumanList.vue'), meta: { modul: 'pengumuman' } },
    { path: '/pengumuman/:id', name: 'pengumuman-detail', component: () => import('../pages/pengumuman/PengumumanDetail.vue'), meta: { modul: 'pengumuman' } },

    { path: '/payslip', name: 'payslip', component: () => import('../pages/payslip/PayslipList.vue'), meta: { modul: 'payslip' } },
    { path: '/payslip/:id', name: 'payslip-detail', component: () => import('../pages/payslip/PayslipDetail.vue'), meta: { modul: 'payslip' } },
    { path: '/payslip-admin', name: 'payslip-admin', component: () => import('../pages/payslip/PayslipAdmin.vue'), meta: { modul: 'payslip_admin' } },

    { path: '/organisasi', name: 'organisasi', component: () => import('../pages/organisasi/StrukturOrganisasi.vue'), meta: { modul: 'organisasi' } },
    { path: '/organisasi/penempatan', name: 'penempatan', component: () => import('../pages/organisasi/PenempatanList.vue'), meta: { modul: 'organisasi' } },
    { path: '/organisasi/delegasi', name: 'delegasi', component: () => import('../pages/organisasi/DelegasiList.vue'), meta: { modul: 'organisasi' } },
    { path: '/organisasi/approval-matrix', name: 'approval-matrix', component: () => import('../pages/organisasi/ApprovalMatrixList.vue'), meta: { modul: 'organisasi' } },

    { path: '/master/:key', name: 'master', component: () => import('../pages/MasterCrud.vue'), meta: { modul: 'master_data' } },

    { path: '/laporan', name: 'laporan', component: () => import('../pages/Reporting.vue'), meta: { modul: 'reporting' } },

    { path: '/admin/users', name: 'users', component: () => import('../pages/admin/Users.vue'), meta: { modul: 'administrasi_sistem' } },
    { path: '/admin/roles', name: 'roles', component: () => import('../pages/admin/Roles.vue'), meta: { modul: 'administrasi_sistem' } },
    { path: '/admin/konfigurasi', name: 'konfigurasi', component: () => import('../pages/admin/Konfigurasi.vue'), meta: { modul: 'konfigurasi' } },
    { path: '/admin/audit', name: 'audit', component: () => import('../pages/admin/Audit.vue'), meta: { modul: 'audit_trail' } },

    { path: '/403', name: 'forbidden', component: () => import('../pages/Forbidden.vue') },
    { path: '/:pathMatch(.*)*', name: 'notfound', component: () => import('../pages/NotFound.vue') },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: () => ({ top: 0 }),
});

router.beforeEach((to) => {
    const auth = useAuth();
    if (to.meta.public) {
        if (auth.isAuthenticated && to.name === 'login') return { name: 'dashboard' };
        return true;
    }
    if (!auth.isAuthenticated) return { name: 'login', query: { redirect: to.fullPath } };
    if (to.meta.modul && !auth.can(to.meta.modul, to.meta.ability || 'R')) return { name: 'forbidden' };
    return true;
});

export default router;
