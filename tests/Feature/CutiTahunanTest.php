<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class CutiTahunanTest extends TestCase
{
    use SeedsDatabase;

    private array $validData = [
        'tanggal_mulai' => '2026-08-10',
        'tanggal_selesai' => '2026-08-12',
        'alasan' => 'Liburan keluarga',
    ];

    public function test_create(): void
    {
        $this->pegawai();
        $r = $this->postJson('/api/cuti-tahunan', $this->validData);
        $r->assertStatus(201)->assertJsonPath('data.status', 'menunggu');
        $this->assertNotNull($r->json('data.jumlah_hari'));
    }

    public function test_list(): void
    {
        $this->pegawai();
        $this->postJson('/api/cuti-tahunan', $this->validData);
        $this->getJson('/api/cuti-tahunan')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_saldo(): void
    {
        $this->pegawai();
        $r = $this->getJson('/api/cuti-tahunan-saldo');
        $r->assertOk()->assertJsonStructure(['data' => ['saldo_awal', 'terpakai', 'sisa']]);
    }

    public function test_approve_deducts_balance(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/cuti-tahunan', $this->validData)->json('data.id');
        $saldoBefore = $this->getJson('/api/cuti-tahunan-saldo')->json('data.sisa');

        $this->admin();
        $this->postJson("/api/cuti-tahunan/{$id}/approve")->assertOk();

        $this->pegawai();
        $saldoAfter = $this->getJson('/api/cuti-tahunan-saldo')->json('data.sisa');
        $this->assertLessThan($saldoBefore, $saldoAfter);
    }

    public function test_reject(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/cuti-tahunan', $this->validData)->json('data.id');

        $this->admin();
        $this->postJson("/api/cuti-tahunan/{$id}/reject")->assertOk();
    }

    public function test_show(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/cuti-tahunan', $this->validData)->json('data.id');
        $this->getJson("/api/cuti-tahunan/{$id}")->assertOk();
    }

    public function test_update_pending(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/cuti-tahunan', $this->validData)->json('data.id');
        $this->putJson("/api/cuti-tahunan/{$id}", array_merge($this->validData, ['alasan' => 'Updated']))
             ->assertOk();
    }

    public function test_validation(): void
    {
        $this->pegawai();
        $this->postJson('/api/cuti-tahunan', [])->assertStatus(422);
    }
}
