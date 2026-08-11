<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class CutiBesarTest extends TestCase
{
    use SeedsDatabase;

    private array $validData = [
        'tanggal_mulai' => '2026-09-01',
        'tanggal_selesai' => '2026-09-30',
        'jumlah_hari' => 30,
        'alasan' => 'Masa kerja 6 tahun',
    ];

    public function test_create(): void
    {
        $this->pegawai();
        $this->postJson('/api/cuti-besar', $this->validData)
             ->assertStatus(201)->assertJsonPath('data.status', 'menunggu');
    }

    public function test_list(): void
    {
        $this->pegawai();
        $this->postJson('/api/cuti-besar', $this->validData);
        $this->getJson('/api/cuti-besar')->assertOk();
    }

    public function test_approve(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/cuti-besar', $this->validData)->json('data.id');

        $this->admin();
        $this->postJson("/api/cuti-besar/{$id}/approve")->assertOk();
    }

    public function test_reject(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/cuti-besar', $this->validData)->json('data.id');

        $this->admin();
        $this->postJson("/api/cuti-besar/{$id}/reject")->assertOk();
    }

    public function test_show(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/cuti-besar', $this->validData)->json('data.id');
        $this->getJson("/api/cuti-besar/{$id}")->assertOk();
    }

    public function test_validation(): void
    {
        $this->pegawai();
        $this->postJson('/api/cuti-besar', [])->assertStatus(422);
    }
}
