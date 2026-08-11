<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class MasterDataTest extends TestCase
{
    use SeedsDatabase;

    public function test_list_kantor(): void
    {
        $this->admin();
        $r = $this->getJson('/api/master/kantor');
        $r->assertOk()->assertJsonStructure(['data']);
    }

    public function test_create_kantor(): void
    {
        $this->admin();
        $r = $this->postJson('/api/master/kantor', [
            'kode' => 'CB2',
            'nama' => 'Cabang Garut',
            'alamat' => 'Garut',
            'tipe' => 'cabang',
            'latitude' => -7.2,
            'longitude' => 107.9,
            'radius' => 100,
            'status' => 'aktif',
            'id_entitas' => 1,
        ]);
        $r->assertStatus(201);
    }

    public function test_list_unit_kerja(): void
    {
        $this->admin();
        $this->getJson('/api/master/unit-kerja')->assertOk();
    }

    public function test_list_jabatan(): void
    {
        $this->admin();
        $this->getJson('/api/master/jabatan')->assertOk();
    }

    public function test_list_jadwal(): void
    {
        $this->admin();
        $this->getJson('/api/master/jadwal')->assertOk();
    }

    public function test_list_hari_libur(): void
    {
        $this->admin();
        $r = $this->getJson('/api/master/hari-libur');
        $r->assertOk();
        $this->assertGreaterThan(0, count($r->json('data')));
    }

    public function test_create_hari_libur(): void
    {
        $this->admin();
        $r = $this->postJson('/api/master/hari-libur', [
            'tanggal' => '2026-12-31',
            'nama' => 'Cuti Bersama Tahun Baru',
            'tipe' => 'cuti_bersama',
            'is_recurring' => false,
        ]);
        $r->assertStatus(201);
    }

    public function test_list_jenis_izin(): void
    {
        $this->admin();
        $this->getJson('/api/master/jenis-izin')->assertOk();
    }

    public function test_list_jenis_pengumuman(): void
    {
        $this->admin();
        $this->getJson('/api/master/jenis-pengumuman')->assertOk();
    }

    public function test_list_komponen_gaji(): void
    {
        $this->admin();
        $this->getJson('/api/master/komponen-gaji')->assertOk();
    }

    public function test_pegawai_cannot_access_master(): void
    {
        $this->pegawai();
        $this->getJson('/api/master/kantor')->assertStatus(403);
    }

    public function test_update_kantor(): void
    {
        $this->admin();
        $kantor = $this->getJson('/api/master/kantor')->json('data.0');
        $this->putJson("/api/master/kantor/{$kantor['id']}", array_merge($kantor, ['nama' => 'Updated']))
             ->assertOk();
    }

    public function test_show_kantor(): void
    {
        $this->admin();
        $kantor = $this->getJson('/api/master/kantor')->json('data.0');
        $this->getJson("/api/master/kantor/{$kantor['id']}")->assertOk();
    }
}
