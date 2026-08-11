<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $allModules = [
            'dashboard', 'dashboard_supervisor', 'dashboard_hr',
            'presensi', 'presensi_tim', 'verifikasi',
            'dinas_luar', 'wfh', 'wfa',
            'izin', 'cuti_tahunan', 'block_leave', 'cuti_melahirkan', 'cuti_besar',
            'lembur', 'pengumuman', 'payslip', 'payslip_admin',
            'organisasi', 'master_data', 'reporting',
        ];

        $allAbilities = ['C', 'R', 'U', 'D', 'A', 'P'];

        $adminAkses = [];
        foreach ($allModules as $modul) {
            $adminAkses[$modul] = $allAbilities;
        }

        $approvalModules = [
            'dinas_luar', 'wfh', 'wfa', 'izin',
            'cuti_tahunan', 'block_leave', 'cuti_melahirkan', 'cuti_besar', 'lembur',
        ];

        $manajemenAkses = [
            'dashboard'            => ['R'],
            'dashboard_supervisor' => ['R'],
            'dashboard_hr'         => ['R'],
            'presensi_tim'         => ['R'],
            'reporting'            => ['R'],
        ];
        foreach ($approvalModules as $modul) {
            $manajemenAkses[$modul] = ['A'];
        }

        $roles = [
            [
                'slug'      => 'administrator',
                'nama'      => 'Administrator Sistem',
                'deskripsi' => 'Akses penuh ke seluruh modul sistem',
                'hak_akses' => $adminAkses,
                'is_system' => true,
            ],
            [
                'slug'      => 'pegawai',
                'nama'      => 'Pegawai',
                'deskripsi' => 'Akses dasar untuk pegawai',
                'hak_akses' => [
                    'dashboard'       => ['R'],
                    'presensi'        => ['C', 'R'],
                    'dinas_luar'      => ['C', 'R'],
                    'wfh'             => ['C', 'R'],
                    'wfa'             => ['C', 'R'],
                    'izin'            => ['C', 'R'],
                    'cuti_tahunan'    => ['C', 'R'],
                    'block_leave'     => ['C', 'R'],
                    'cuti_melahirkan' => ['C', 'R'],
                    'cuti_besar'      => ['C', 'R'],
                    'lembur'          => ['C', 'R'],
                    'pengumuman'      => ['R'],
                    'payslip'         => ['R'],
                ],
                'is_system' => false,
            ],
            [
                'slug'      => 'supervisor',
                'nama'      => 'Supervisor',
                'deskripsi' => 'Pegawai dengan hak approval dan monitoring tim',
                'hak_akses' => [
                    'dashboard'            => ['R'],
                    'dashboard_supervisor'  => ['R'],
                    'presensi'             => ['C', 'R'],
                    'presensi_tim'         => ['R'],
                    'verifikasi'           => ['R', 'A'],
                    'dinas_luar'           => ['C', 'R', 'A'],
                    'wfh'                  => ['C', 'R', 'A'],
                    'wfa'                  => ['C', 'R', 'A'],
                    'izin'                 => ['C', 'R', 'A'],
                    'cuti_tahunan'         => ['C', 'R', 'A'],
                    'block_leave'          => ['C', 'R', 'A'],
                    'cuti_melahirkan'      => ['C', 'R', 'A'],
                    'cuti_besar'           => ['C', 'R', 'A'],
                    'lembur'               => ['C', 'R', 'A'],
                    'pengumuman'           => ['R'],
                    'payslip'              => ['R'],
                ],
                'is_system' => false,
            ],
            [
                'slug'      => 'hr',
                'nama'      => 'Human Resources',
                'deskripsi' => 'Manajemen SDM dan administrasi kepegawaian',
                'hak_akses' => [
                    'dashboard_hr'    => ['R'],
                    'presensi'        => ['C', 'R', 'U', 'D'],
                    'presensi_tim'    => ['R'],
                    'verifikasi'      => ['R', 'A'],
                    'dinas_luar'      => ['C', 'R', 'U', 'D', 'A'],
                    'wfh'             => ['C', 'R', 'U', 'D', 'A'],
                    'wfa'             => ['C', 'R', 'U', 'D', 'A'],
                    'izin'            => ['C', 'R', 'U', 'D', 'A'],
                    'cuti_tahunan'    => ['C', 'R', 'U', 'D', 'A'],
                    'block_leave'     => ['C', 'R', 'U', 'D', 'A'],
                    'cuti_melahirkan' => ['C', 'R', 'U', 'D', 'A'],
                    'cuti_besar'      => ['C', 'R', 'U', 'D', 'A'],
                    'lembur'          => ['C', 'R', 'U', 'D', 'A'],
                    'pengumuman'      => ['C', 'R', 'U', 'D', 'P'],
                    'organisasi'      => ['C', 'R', 'U', 'D'],
                    'master_data'     => ['C', 'R', 'U', 'D'],
                    'reporting'       => ['R'],
                ],
                'is_system' => false,
            ],
            [
                'slug'      => 'payroll',
                'nama'      => 'Payroll',
                'deskripsi' => 'Pengelolaan gaji dan slip gaji',
                'hak_akses' => [
                    'dashboard'     => ['R'],
                    'payslip'       => ['R'],
                    'payslip_admin' => ['C', 'R', 'U', 'P'],
                    'reporting'     => ['R'],
                ],
                'is_system' => false,
            ],
            [
                'slug'      => 'manajemen',
                'nama'      => 'Manajemen',
                'deskripsi' => 'Level direksi dengan hak monitoring dan approval',
                'hak_akses' => $manajemenAkses,
                'is_system' => false,
            ],
            [
                'slug'      => 'admin_kantor',
                'nama'      => 'Admin Kantor',
                'deskripsi' => 'Administrasi presensi dan verifikasi di level kantor',
                'hak_akses' => [
                    'dashboard'    => ['R'],
                    'presensi'     => ['C', 'R', 'U', 'D'],
                    'presensi_tim' => ['R'],
                    'verifikasi'   => ['R', 'A'],
                    'organisasi'   => ['R'],
                    'master_data'  => ['R'],
                    'reporting'    => ['R'],
                ],
                'is_system' => false,
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
