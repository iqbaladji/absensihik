<?php

namespace Database\Seeders;

use App\Models\Departemen;
use App\Models\Direktorat;
use App\Models\Divisi;
use App\Models\Entitas;
use App\Models\Jabatan;
use App\Models\Kantor;
use App\Models\UnitKerja;
use Illuminate\Database\Seeder;

class OrganisasiSeeder extends Seeder
{
    public function run(): void
    {
        $entitas = Entitas::create([
            'kode'   => 'BPRS-HIK',
            'nama'   => 'BPRS HIK Parahyangan',
            'alamat' => 'Bandung, Jawa Barat',
            'status' => 'aktif',
        ]);

        // --- Direktorat ---------------------------------------------------

        $dirUtama = Direktorat::create([
            'id_entitas' => $entitas->id,
            'kode'       => 'DIR-UTM',
            'nama'       => 'Direktorat Utama',
        ]);

        $dirOps = Direktorat::create([
            'id_entitas' => $entitas->id,
            'kode'       => 'DIR-OPS',
            'nama'       => 'Direktorat Operasional',
        ]);

        $dirBis = Direktorat::create([
            'id_entitas' => $entitas->id,
            'kode'       => 'DIR-BIS',
            'nama'       => 'Direktorat Bisnis',
        ]);

        // --- Divisi -------------------------------------------------------

        $divSDM = Divisi::create([
            'id_direktorat' => $dirUtama->id,
            'kode'          => 'DIV-SDM',
            'nama'          => 'Divisi SDM & Umum',
        ]);

        $divKepatuhan = Divisi::create([
            'id_direktorat' => $dirUtama->id,
            'kode'          => 'DIV-KPT',
            'nama'          => 'Divisi Kepatuhan',
        ]);

        $divOps = Divisi::create([
            'id_direktorat' => $dirOps->id,
            'kode'          => 'DIV-OPS',
            'nama'          => 'Divisi Operasional',
        ]);

        $divIT = Divisi::create([
            'id_direktorat' => $dirOps->id,
            'kode'          => 'DIV-IT',
            'nama'          => 'Divisi IT',
        ]);

        $divBis = Divisi::create([
            'id_direktorat' => $dirBis->id,
            'kode'          => 'DIV-BIS',
            'nama'          => 'Divisi Bisnis',
        ]);

        // --- Departemen ---------------------------------------------------

        $deptSDM = Departemen::create([
            'id_divisi' => $divSDM->id,
            'kode'      => 'DPT-SDM',
            'nama'      => 'Dept. SDM',
        ]);

        Departemen::create([
            'id_divisi' => $divSDM->id,
            'kode'      => 'DPT-UMM',
            'nama'      => 'Dept. Umum',
        ]);

        $deptOps = Departemen::create([
            'id_divisi' => $divOps->id,
            'kode'      => 'DPT-OPS',
            'nama'      => 'Dept. Operasional',
        ]);

        Departemen::create([
            'id_divisi' => $divOps->id,
            'kode'      => 'DPT-AKT',
            'nama'      => 'Dept. Akuntansi',
        ]);

        $deptIT = Departemen::create([
            'id_divisi' => $divIT->id,
            'kode'      => 'DPT-IT',
            'nama'      => 'Dept. IT',
        ]);

        $deptPembiayaan = Departemen::create([
            'id_divisi' => $divBis->id,
            'kode'      => 'DPT-PBY',
            'nama'      => 'Dept. Pembiayaan',
        ]);

        Departemen::create([
            'id_divisi' => $divBis->id,
            'kode'      => 'DPT-PHD',
            'nama'      => 'Dept. Penghimpunan Dana',
        ]);

        // --- Kantor -------------------------------------------------------

        $kantorPusat = Kantor::create([
            'id_entitas' => $entitas->id,
            'kode'       => 'KP',
            'nama'       => 'Kantor Pusat',
            'alamat'     => 'Jl. Merdeka No. 1, Bandung',
            'latitude'   => -6.9175000,
            'longitude'  => 107.6191000,
            'radius'     => 100,
            'status'     => 'aktif',
        ]);

        $kantorCimahi = Kantor::create([
            'id_entitas' => $entitas->id,
            'kode'       => 'CB1',
            'nama'       => 'Cabang Cimahi',
            'alamat'     => 'Jl. Raya Cimahi, Cimahi',
            'latitude'   => -6.8722000,
            'longitude'  => 107.5418000,
            'radius'     => 100,
            'status'     => 'aktif',
        ]);

        $kantorKas = Kantor::create([
            'id_entitas' => $entitas->id,
            'kode'       => 'KAS1',
            'nama'       => 'Kas Padalarang',
            'alamat'     => 'Jl. Raya Padalarang, Bandung Barat',
            'latitude'   => -6.8401000,
            'longitude'  => 107.4635000,
            'radius'     => 100,
            'status'     => 'aktif',
        ]);

        // --- Unit Kerja ---------------------------------------------------

        UnitKerja::create([
            'id_departemen' => $deptSDM->id,
            'id_kantor'     => $kantorPusat->id,
            'kode'          => 'UNT-SDM',
            'nama'          => 'Unit SDM',
        ]);

        UnitKerja::create([
            'id_departemen' => $deptIT->id,
            'id_kantor'     => $kantorPusat->id,
            'kode'          => 'UNT-IT',
            'nama'          => 'Unit IT',
        ]);

        UnitKerja::create([
            'id_departemen' => $deptOps->id,
            'id_kantor'     => $kantorPusat->id,
            'kode'          => 'UNT-OPS',
            'nama'          => 'Unit Operasional',
        ]);

        UnitKerja::create([
            'id_departemen' => $deptPembiayaan->id,
            'id_kantor'     => $kantorPusat->id,
            'kode'          => 'UNT-BIS',
            'nama'          => 'Unit Bisnis',
        ]);

        UnitKerja::create([
            'id_departemen' => null,
            'id_kantor'     => $kantorPusat->id,
            'kode'          => 'UNT-KPT',
            'nama'          => 'Unit Kepatuhan',
        ]);

        UnitKerja::create([
            'id_departemen' => null,
            'id_kantor'     => $kantorCimahi->id,
            'kode'          => 'UNT-LYN',
            'nama'          => 'Unit Layanan',
        ]);

        UnitKerja::create([
            'id_departemen' => null,
            'id_kantor'     => $kantorCimahi->id,
            'kode'          => 'UNT-BSC',
            'nama'          => 'Unit Bisnis Cabang',
        ]);

        UnitKerja::create([
            'id_departemen' => null,
            'id_kantor'     => $kantorKas->id,
            'kode'          => 'UNT-LYK',
            'nama'          => 'Unit Layanan Kas',
        ]);

        // --- Jabatan ------------------------------------------------------

        Jabatan::create(['kode' => 'DIR',    'nama' => 'Direktur Utama',       'level' => 1]);
        Jabatan::create(['kode' => 'DIROPS', 'nama' => 'Direktur Operasional', 'level' => 1]);
        Jabatan::create(['kode' => 'DIRBIS', 'nama' => 'Direktur Bisnis',      'level' => 1]);
        Jabatan::create(['kode' => 'KADIV',  'nama' => 'Kepala Divisi',        'level' => 2]);
        Jabatan::create(['kode' => 'KADEPT', 'nama' => 'Kepala Departemen',    'level' => 3]);
        Jabatan::create(['kode' => 'KAUNIT', 'nama' => 'Kepala Unit',          'level' => 4]);
        Jabatan::create(['kode' => 'STAFF',  'nama' => 'Staff',               'level' => 5]);
        Jabatan::create(['kode' => 'TELLER', 'nama' => 'Teller',              'level' => 5]);
        Jabatan::create(['kode' => 'CS',     'nama' => 'Customer Service',     'level' => 5]);
    }
}
