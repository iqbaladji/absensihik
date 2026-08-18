<?php

namespace Database\Seeders;

use App\Models\HariLibur;
use App\Models\Jadwal;
use App\Models\JenisIzin;
use App\Models\JenisPengumuman;
use App\Models\KomponenGaji;
use App\Models\Shift;
use Illuminate\Database\Seeder;

class ReferensiSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedJadwalShift();
        $this->seedHariLibur();
        $this->seedJenisIzin();
        $this->seedJenisPengumuman();
        $this->seedKomponenGaji();
    }

    private function seedJadwalShift(): void
    {
        $reguler = Jadwal::create([
            'kode'   => 'REG',
            'nama'   => 'Reguler',
            'tipe'   => 'reguler',
            'status' => 'aktif',
        ]);

        $hariKerja = ['senin', 'selasa', 'rabu', 'kamis', 'jumat'];
        $hariLibur = ['sabtu', 'minggu'];

        foreach ($hariKerja as $hari) {
            Shift::create([
                'id_jadwal'              => $reguler->id,
                'nama'                   => 'Reguler',
                'hari'                   => $hari,
                'jam_masuk'              => '08:00',
                'jam_keluar'             => '17:00',
                'toleransi_terlambat'    => 10,
                'toleransi_pulang_awal'  => 0,
                'is_libur'               => false,
            ]);
        }

        foreach ($hariLibur as $hari) {
            Shift::create([
                'id_jadwal'              => $reguler->id,
                'nama'                   => 'Libur',
                'hari'                   => $hari,
                'jam_masuk'              => '00:00',
                'jam_keluar'             => '00:00',
                'toleransi_terlambat'    => 0,
                'toleransi_pulang_awal'  => 0,
                'is_libur'               => true,
            ]);
        }

        $shiftJadwal = Jadwal::create([
            'kode'   => 'SFT',
            'nama'   => 'Shift',
            'tipe'   => 'shift',
            'status' => 'aktif',
        ]);

        $shiftDefinitions = [
            ['nama' => 'Pagi',   'jam_masuk' => '06:00', 'jam_keluar' => '14:00'],
            ['nama' => 'Siang',  'jam_masuk' => '14:00', 'jam_keluar' => '22:00'],
            ['nama' => 'Malam',  'jam_masuk' => '22:00', 'jam_keluar' => '06:00'],
        ];

        foreach ($shiftDefinitions as $def) {
            foreach ($hariKerja as $hari) {
                Shift::create([
                    'id_jadwal'              => $shiftJadwal->id,
                    'nama'                   => $def['nama'],
                    'hari'                   => $hari,
                    'jam_masuk'              => $def['jam_masuk'],
                    'jam_keluar'             => $def['jam_keluar'],
                    'toleransi_terlambat'    => 10,
                    'toleransi_pulang_awal'  => 0,
                    'is_libur'               => false,
                ]);
            }
        }
    }

    private function seedHariLibur(): void
    {
        $libur = [
            ['tanggal' => '2026-01-01', 'nama' => 'Tahun Baru Masehi'],
            ['tanggal' => '2026-01-29', 'nama' => 'Isra Mi\'raj Nabi Muhammad SAW'],
            ['tanggal' => '2026-03-31', 'nama' => 'Hari Raya Nyepi'],
            ['tanggal' => '2026-04-01', 'nama' => 'Hari Raya Idul Fitri'],
            ['tanggal' => '2026-04-02', 'nama' => 'Hari Raya Idul Fitri'],
            ['tanggal' => '2026-05-01', 'nama' => 'Hari Buruh Internasional'],
            ['tanggal' => '2026-05-29', 'nama' => 'Hari Raya Waisak'],
            ['tanggal' => '2026-06-01', 'nama' => 'Hari Lahir Pancasila'],
            ['tanggal' => '2026-06-07', 'nama' => 'Hari Raya Idul Adha'],
            ['tanggal' => '2026-06-27', 'nama' => 'Tahun Baru Islam'],
            ['tanggal' => '2026-08-17', 'nama' => 'Hari Kemerdekaan RI'],
            ['tanggal' => '2026-09-05', 'nama' => 'Maulid Nabi Muhammad SAW'],
            ['tanggal' => '2026-12-25', 'nama' => 'Hari Raya Natal'],
        ];

        foreach ($libur as $item) {
            HariLibur::create([
                'tanggal'      => $item['tanggal'],
                'nama'         => $item['nama'],
                'tipe'         => 'nasional',
                'is_recurring' => false,
            ]);
        }
    }

    private function seedJenisIzin(): void
    {
        $jenisIzin = [
            ['kode' => 'SAK', 'nama' => 'Sakit',                   'maks_hari' => 14, 'perlu_lampiran' => true,  'potong_cuti' => false],
            ['kode' => 'IZN', 'nama' => 'Izin Pribadi',            'maks_hari' => 3,  'perlu_lampiran' => false, 'potong_cuti' => true],
            ['kode' => 'DUK', 'nama' => 'Duka/Meninggal Keluarga', 'maks_hari' => 3,  'perlu_lampiran' => true,  'potong_cuti' => false],
            ['kode' => 'NIK', 'nama' => 'Menikah',                 'maks_hari' => 3,  'perlu_lampiran' => true,  'potong_cuti' => false],
            ['kode' => 'KHT', 'nama' => 'Khitan Anak',      'maks_hari' => 2,  'perlu_lampiran' => true,  'potong_cuti' => false],
        ];

        foreach ($jenisIzin as $item) {
            JenisIzin::create(array_merge($item, ['status' => 'aktif']));
        }
    }

    private function seedJenisPengumuman(): void
    {
        $jenis = [
            ['kode' => 'UMM', 'nama' => 'Umum'],
            ['kode' => 'KBJ', 'nama' => 'Kebijakan'],
            ['kode' => 'SDM', 'nama' => 'SDM/HR'],
            ['kode' => 'OPS', 'nama' => 'Operasional'],
        ];

        foreach ($jenis as $item) {
            JenisPengumuman::create(array_merge($item, ['status' => 'aktif']));
        }
    }

    private function seedKomponenGaji(): void
    {
        $komponen = [
            ['kode' => 'GP',    'nama' => 'Gaji Pokok',            'tipe' => 'pendapatan', 'urutan' => 1],
            ['kode' => 'TJ',    'nama' => 'Tunjangan Jabatan',     'tipe' => 'pendapatan', 'urutan' => 2],
            ['kode' => 'TT',    'nama' => 'Tunjangan Transport',   'tipe' => 'pendapatan', 'urutan' => 3],
            ['kode' => 'TM',    'nama' => 'Tunjangan Makan',       'tipe' => 'pendapatan', 'urutan' => 4],
            ['kode' => 'LMB',   'nama' => 'Uang Lembur',           'tipe' => 'pendapatan', 'urutan' => 5],
            ['kode' => 'BPJSK', 'nama' => 'BPJS Kesehatan',        'tipe' => 'potongan',   'urutan' => 1],
            ['kode' => 'BPJST', 'nama' => 'BPJS Ketenagakerjaan',  'tipe' => 'potongan',   'urutan' => 2],
            ['kode' => 'PPH',   'nama' => 'PPh 21',                'tipe' => 'potongan',   'urutan' => 3],
        ];

        foreach ($komponen as $item) {
            KomponenGaji::create(array_merge($item, ['status' => 'aktif']));
        }
    }
}
