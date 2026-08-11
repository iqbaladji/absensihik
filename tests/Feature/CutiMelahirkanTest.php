<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class CutiMelahirkanTest extends TestCase
{
    use SeedsDatabase;

    private array $validData = [
        'tanggal_mulai' => '2026-09-01',
        'jumlah_hari' => 90,
        'tipe' => 'melahirkan',
        'catatan' => 'HPL September',
    ];

    public function test_create(): void
    {
        $this->pegawai();
        $r = $this->postJson('/api/cuti-melahirkan', $this->validData);
        $r->assertStatus(201)->assertJsonPath('data.status', 'menunggu');
    }

    public function test_list(): void
    {
        $this->pegawai();
        $this->postJson('/api/cuti-melahirkan', $this->validData);
        $this->getJson('/api/cuti-melahirkan')->assertOk();
    }

    public function test_approve(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/cuti-melahirkan', $this->validData)->json('data.id');

        $this->admin();
        $this->postJson("/api/cuti-melahirkan/{$id}/approve")->assertOk();
    }

    public function test_reject(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/cuti-melahirkan', $this->validData)->json('data.id');

        $this->admin();
        $this->postJson("/api/cuti-melahirkan/{$id}/reject", ['catatan' => 'Dokumen kurang'])
             ->assertOk();
    }

    public function test_tipe_keguguran(): void
    {
        $this->pegawai();
        $data = array_merge($this->validData, ['tipe' => 'keguguran', 'jumlah_hari' => 45]);
        $this->postJson('/api/cuti-melahirkan', $data)->assertStatus(201);
    }

    public function test_invalid_tipe(): void
    {
        $this->pegawai();
        $data = array_merge($this->validData, ['tipe' => 'invalid']);
        $this->postJson('/api/cuti-melahirkan', $data)->assertStatus(422);
    }

    public function test_validation(): void
    {
        $this->pegawai();
        $this->postJson('/api/cuti-melahirkan', [])->assertStatus(422);
    }
}
