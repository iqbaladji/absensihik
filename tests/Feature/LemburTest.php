<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class LemburTest extends TestCase
{
    use SeedsDatabase;

    private array $validData = [
        'tanggal' => '2026-08-10',
        'jam_mulai_rencana' => '18:00',
        'jam_selesai_rencana' => '21:00',
        'uraian_pekerjaan' => 'Closing laporan bulanan',
    ];

    public function test_create(): void
    {
        $this->pegawai();
        $this->postJson('/api/lembur', $this->validData)
             ->assertStatus(201)->assertJsonPath('data.status', 'menunggu');
    }

    public function test_list(): void
    {
        $this->pegawai();
        $this->postJson('/api/lembur', $this->validData);
        $this->getJson('/api/lembur')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_full_lifecycle(): void
    {
        $this->admin();
        $id = $this->postJson('/api/lembur', $this->validData)->json('data.id');
        $this->postJson("/api/lembur/{$id}/approve")->assertOk();

        $r = $this->postJson("/api/lembur/{$id}/mulai");
        $r->assertOk()->assertJsonPath('data.status', 'berlangsung');
        $this->assertNotNull($r->json('data.jam_mulai_aktual'));

        $r2 = $this->postJson("/api/lembur/{$id}/selesai", ['hasil_pekerjaan' => 'Laporan selesai']);
        $r2->assertOk()->assertJsonPath('data.status', 'selesai');
        $this->assertNotNull($r2->json('data.jam_selesai_aktual'));
    }

    public function test_cannot_mulai_before_approve(): void
    {
        $this->admin();
        $id = $this->postJson('/api/lembur', $this->validData)->json('data.id');
        $this->postJson("/api/lembur/{$id}/mulai")->assertStatus(422);
    }

    public function test_cannot_selesai_before_mulai(): void
    {
        $this->admin();
        $id = $this->postJson('/api/lembur', $this->validData)->json('data.id');
        $this->postJson("/api/lembur/{$id}/approve");
        $this->postJson("/api/lembur/{$id}/selesai", ['hasil_pekerjaan' => 'Test'])->assertStatus(422);
    }

    public function test_selesai_requires_hasil(): void
    {
        $this->admin();
        $id = $this->postJson('/api/lembur', $this->validData)->json('data.id');
        $this->postJson("/api/lembur/{$id}/approve");
        $this->postJson("/api/lembur/{$id}/mulai");
        $this->postJson("/api/lembur/{$id}/selesai", [])->assertStatus(422);
    }

    public function test_reject(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/lembur', $this->validData)->json('data.id');

        $this->admin();
        $this->postJson("/api/lembur/{$id}/reject")->assertOk()->assertJsonPath('data.status', 'ditolak');
    }

    public function test_show(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/lembur', $this->validData)->json('data.id');
        $this->getJson("/api/lembur/{$id}")->assertOk();
    }

    public function test_validation(): void
    {
        $this->pegawai();
        $this->postJson('/api/lembur', [])->assertStatus(422);
    }
}
