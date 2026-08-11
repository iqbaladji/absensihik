<?php

namespace Database\Seeders;

use App\Models\ApprovalMatrix;
use App\Models\Jabatan;
use App\Models\Jadwal;
use App\Models\Kantor;
use App\Models\Penempatan;
use App\Models\Role;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roles   = Role::pluck('id', 'slug');
        $kantor  = Kantor::pluck('id', 'kode');
        $unit    = UnitKerja::pluck('id', 'kode');
        $jabatan = Jabatan::pluck('id', 'kode');
        $jadwal  = Jadwal::where('kode', 'REG')->value('id');

        // --- Users (tanpa atasan dulu) ------------------------------------

        $admin = User::create([
            'name'       => 'Administrator',
            'username'   => 'admin',
            'email'      => 'admin@bprshik.co.id',
            'nip'        => '000001',
            'password'   => 'password',
            'id_role'    => $roles['administrator'],
            'id_kantor'  => $kantor['KP'],
            'id_unit'    => $unit['UNT-IT'],
            'id_jabatan' => null,
            'id_jadwal'  => $jadwal,
            'status'     => 'aktif',
        ]);

        $budi = User::create([
            'name'       => 'Budi Santoso',
            'username'   => 'budi',
            'email'      => 'budi@bprshik.co.id',
            'nip'        => '100001',
            'password'   => 'password',
            'id_role'    => $roles['pegawai'],
            'id_kantor'  => $kantor['KP'],
            'id_unit'    => $unit['UNT-OPS'],
            'id_jabatan' => $jabatan['STAFF'],
            'id_jadwal'  => $jadwal,
            'status'     => 'aktif',
        ]);

        $siti = User::create([
            'name'       => 'Siti Nurhaliza',
            'username'   => 'siti',
            'email'      => 'siti@bprshik.co.id',
            'nip'        => '100002',
            'password'   => 'password',
            'id_role'    => $roles['supervisor'],
            'id_kantor'  => $kantor['KP'],
            'id_unit'    => $unit['UNT-SDM'],
            'id_jabatan' => $jabatan['KAUNIT'],
            'id_jadwal'  => $jadwal,
            'status'     => 'aktif',
        ]);

        $dewi = User::create([
            'name'       => 'Dewi Rahmawati',
            'username'   => 'dewi',
            'email'      => 'dewi@bprshik.co.id',
            'nip'        => '100003',
            'password'   => 'password',
            'id_role'    => $roles['hr'],
            'id_kantor'  => $kantor['KP'],
            'id_unit'    => $unit['UNT-SDM'],
            'id_jabatan' => $jabatan['KADEPT'],
            'id_jadwal'  => $jadwal,
            'status'     => 'aktif',
        ]);

        $andi = User::create([
            'name'       => 'Andi Wijaya',
            'username'   => 'andi',
            'email'      => 'andi@bprshik.co.id',
            'nip'        => '100004',
            'password'   => 'password',
            'id_role'    => $roles['payroll'],
            'id_kantor'  => $kantor['KP'],
            'id_unit'    => $unit['UNT-SDM'],
            'id_jabatan' => $jabatan['STAFF'],
            'id_jadwal'  => $jadwal,
            'status'     => 'aktif',
        ]);

        $hendra = User::create([
            'name'       => 'Hendra Kusuma',
            'username'   => 'hendra',
            'email'      => 'hendra@bprshik.co.id',
            'nip'        => '100005',
            'password'   => 'password',
            'id_role'    => $roles['manajemen'],
            'id_kantor'  => $kantor['KP'],
            'id_unit'    => null,
            'id_jabatan' => $jabatan['DIR'],
            'id_jadwal'  => $jadwal,
            'status'     => 'aktif',
        ]);

        $rina = User::create([
            'name'       => 'Rina Permata',
            'username'   => 'rina',
            'email'      => 'rina@bprshik.co.id',
            'nip'        => '100006',
            'password'   => 'password',
            'id_role'    => $roles['admin_kantor'],
            'id_kantor'  => $kantor['CB1'],
            'id_unit'    => $unit['UNT-LYN'],
            'id_jabatan' => $jabatan['KAUNIT'],
            'id_jadwal'  => $jadwal,
            'status'     => 'aktif',
        ]);

        // --- Atasan relationships -----------------------------------------

        $budi->update(['id_atasan' => $siti->id]);
        $siti->update(['id_atasan' => $dewi->id]);
        $andi->update(['id_atasan' => $dewi->id]);
        $dewi->update(['id_atasan' => $hendra->id]);
        $rina->update(['id_atasan' => $hendra->id]);

        // --- Penempatan ---------------------------------------------------

        $penempatanData = [
            ['id_user' => $admin->id,  'id_kantor' => $kantor['KP'],  'id_unit' => $unit['UNT-IT'],  'id_jabatan' => null],
            ['id_user' => $budi->id,   'id_kantor' => $kantor['KP'],  'id_unit' => $unit['UNT-OPS'], 'id_jabatan' => $jabatan['STAFF']],
            ['id_user' => $siti->id,   'id_kantor' => $kantor['KP'],  'id_unit' => $unit['UNT-SDM'], 'id_jabatan' => $jabatan['KAUNIT']],
            ['id_user' => $dewi->id,   'id_kantor' => $kantor['KP'],  'id_unit' => $unit['UNT-SDM'], 'id_jabatan' => $jabatan['KADEPT']],
            ['id_user' => $andi->id,   'id_kantor' => $kantor['KP'],  'id_unit' => $unit['UNT-SDM'], 'id_jabatan' => $jabatan['STAFF']],
            ['id_user' => $hendra->id, 'id_kantor' => $kantor['KP'],  'id_unit' => null,             'id_jabatan' => $jabatan['DIR']],
            ['id_user' => $rina->id,   'id_kantor' => $kantor['CB1'], 'id_unit' => $unit['UNT-LYN'], 'id_jabatan' => $jabatan['KAUNIT']],
        ];

        foreach ($penempatanData as $p) {
            Penempatan::create(array_merge($p, [
                'tanggal_mulai' => '2025-01-01',
                'is_aktif'      => true,
            ]));
        }

        // --- Approval Matrix ----------------------------------------------

        $approvalModules = [
            'izin', 'cuti_tahunan', 'block_leave',
            'cuti_melahirkan', 'cuti_besar', 'lembur',
            'dinas_luar', 'wfh', 'wfa',
        ];

        foreach ($approvalModules as $modul) {
            ApprovalMatrix::create([
                'modul'               => $modul,
                'id_jabatan_pemohon'  => $jabatan['STAFF'],
                'id_unit'             => null,
                'urutan'              => 1,
                'tipe_approver'       => 'atasan_langsung',
                'id_jabatan_approver' => $jabatan['KAUNIT'],
                'id_user_approver'    => null,
                'is_aktif'            => true,
            ]);

            ApprovalMatrix::create([
                'modul'               => $modul,
                'id_jabatan_pemohon'  => $jabatan['STAFF'],
                'id_unit'             => null,
                'urutan'              => 2,
                'tipe_approver'       => 'jabatan',
                'id_jabatan_approver' => $jabatan['KADEPT'],
                'id_user_approver'    => null,
                'is_aktif'            => true,
            ]);

            ApprovalMatrix::create([
                'modul'               => $modul,
                'id_jabatan_pemohon'  => $jabatan['KAUNIT'],
                'id_unit'             => null,
                'urutan'              => 1,
                'tipe_approver'       => 'jabatan',
                'id_jabatan_approver' => $jabatan['KADEPT'],
                'id_user_approver'    => null,
                'is_aktif'            => true,
            ]);

            ApprovalMatrix::create([
                'modul'               => $modul,
                'id_jabatan_pemohon'  => $jabatan['KADEPT'],
                'id_unit'             => null,
                'urutan'              => 1,
                'tipe_approver'       => 'jabatan',
                'id_jabatan_approver' => $jabatan['DIR'],
                'id_user_approver'    => null,
                'is_aktif'            => true,
            ]);
        }
    }
}
