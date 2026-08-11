export const navGroups = [
    {
        label: 'Utama',
        items: [
            { label: 'Dashboard', to: '/', icon: '\u{1F4CA}', modul: 'dashboard' },
            { label: 'Dashboard Supervisor', to: '/dashboard/supervisor', icon: '\u{1F465}', modul: 'dashboard_supervisor' },
            { label: 'Dashboard HR', to: '/dashboard/hr', icon: '\u{1F4C8}', modul: 'dashboard_hr' },
        ],
    },
    {
        label: 'Kehadiran',
        items: [
            { label: 'Presensi', to: '/presensi', icon: '⏰', modul: 'presensi' },
            { label: 'Riwayat Presensi', to: '/presensi/riwayat', icon: '\u{1F4C5}', modul: 'presensi' },
            { label: 'Tim Saya', to: '/presensi/tim', icon: '\u{1F465}', modul: 'presensi_tim' },
            { label: 'Verifikasi', to: '/presensi/verifikasi', icon: '✅', modul: 'verifikasi' },
            { label: 'Koreksi', to: '/presensi/koreksi', icon: '✏️', modul: 'presensi' },
        ],
    },
    {
        label: 'Tugas & Lokasi',
        items: [
            { label: 'Dinas Luar', to: '/dinas-luar', icon: '\u{1F697}', modul: 'dinas_luar' },
            { label: 'WFH', to: '/wfh', icon: '\u{1F3E0}', modul: 'wfh' },
            { label: 'WFA', to: '/wfa', icon: '\u{1F30D}', modul: 'wfa' },
        ],
    },
    {
        label: 'Izin & Cuti',
        items: [
            { label: 'Izin', to: '/izin', icon: '\u{1F4DD}', modul: 'izin' },
            { label: 'Cuti Tahunan', to: '/cuti-tahunan', icon: '\u{1F3D6}️', modul: 'cuti_tahunan' },
            { label: 'Block Leave', to: '/block-leave', icon: '\u{1F4E6}', modul: 'block_leave' },
            { label: 'Cuti Melahirkan', to: '/cuti-melahirkan', icon: '\u{1F476}', modul: 'cuti_melahirkan' },
            { label: 'Cuti Besar', to: '/cuti-besar', icon: '\u{1F396}️', modul: 'cuti_besar' },
            { label: 'Saldo Cuti', to: '/saldo-cuti', icon: '\u{1F4B0}', modul: 'cuti_tahunan' },
        ],
    },
    {
        label: 'Lembur',
        items: [
            { label: 'Pengajuan Lembur', to: '/lembur', icon: '\u{23F1}️', modul: 'lembur' },
        ],
    },
    {
        label: 'Pengumuman',
        items: [
            { label: 'Pengumuman', to: '/pengumuman', icon: '\u{1F4E2}', modul: 'pengumuman' },
        ],
    },
    {
        label: 'Payslip',
        items: [
            { label: 'Slip Gaji Saya', to: '/payslip', icon: '\u{1F4B5}', modul: 'payslip' },
            { label: 'Kelola Payslip', to: '/payslip-admin', icon: '\u{1F4CA}', modul: 'payslip_admin' },
        ],
    },
    {
        label: 'Master & Organisasi',
        items: [
            {
                label: 'Master Data', icon: '\u{1F5C2}️', modul: 'master_data',
                children: [
                    { label: 'Kantor', to: '/master/kantor' },
                    { label: 'Unit Kerja', to: '/master/unit-kerja' },
                    { label: 'Jabatan', to: '/master/jabatan' },
                    { label: 'Jadwal', to: '/master/jadwal' },
                    { label: 'Hari Libur', to: '/master/hari-libur' },
                    { label: 'Jenis Izin', to: '/master/jenis-izin' },
                    { label: 'Jenis Pengumuman', to: '/master/jenis-pengumuman' },
                    { label: 'Komponen Gaji', to: '/master/komponen-gaji' },
                ],
            },
            { label: 'Struktur Organisasi', to: '/organisasi', icon: '\u{1F3DB}️', modul: 'organisasi' },
            { label: 'Penempatan', to: '/organisasi/penempatan', icon: '\u{1F4CB}', modul: 'organisasi' },
            { label: 'Delegasi', to: '/organisasi/delegasi', icon: '\u{1F91D}', modul: 'organisasi' },
            { label: 'Approval Matrix', to: '/organisasi/approval-matrix', icon: '\u{1F5C3}️', modul: 'organisasi' },
        ],
    },
    {
        label: 'Laporan',
        items: [
            { label: 'Laporan', to: '/laporan', icon: '\u{1F4D1}', modul: 'reporting' },
        ],
    },
    {
        label: 'Administrasi',
        items: [
            { label: 'Pengguna', to: '/admin/users', icon: '\u{1F464}', modul: 'administrasi_sistem' },
            { label: 'Peran & Hak Akses', to: '/admin/roles', icon: '\u{1F511}', modul: 'administrasi_sistem' },
            { label: 'Konfigurasi', to: '/admin/konfigurasi', icon: '⚙️', modul: 'konfigurasi' },
            { label: 'Jejak Audit', to: '/admin/audit', icon: '\u{1F9FE}', modul: 'audit_trail' },
        ],
    },
];
