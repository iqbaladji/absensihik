<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class BlockLeaveTest extends TestCase
{
    use SeedsDatabase;

    public function test_create(): void
    {
        $this->pegawai();
        $r = $this->postJson('/api/block-leave', [
            'tanggal_mulai' => '2026-08-03',
            'alasan' => 'Block leave tahunan',
        ]);
        $r->assertStatus(201)
          ->assertJsonPath('data.status', 'menunggu')
          ->assertJsonPath('data.jumlah_hari_kerja', 5);
        $this->assertNotNull($r->json('data.tanggal_selesai'));
    }

    public function test_list(): void
    {
        $this->pegawai();
        $this->postJson('/api/block-leave', ['tanggal_mulai' => '2026-08-03', 'alasan' => 'Test']);
        $this->getJson('/api/block-leave')->assertOk();
    }

    public function test_approve_deducts_balance(): void
    {
        $this->pegawai();
        $saldoBefore = $this->getJson('/api/cuti-tahunan-saldo')->json('data.sisa');
        $id = $this->postJson('/api/block-leave', ['tanggal_mulai' => '2026-08-03'])->json('data.id');

        $this->admin();
        $this->postJson("/api/block-leave/{$id}/approve")->assertOk();

        $this->pegawai();
        $saldoAfter = $this->getJson('/api/cuti-tahunan-saldo')->json('data.sisa');
        $this->assertEquals($saldoBefore - 5, $saldoAfter);
    }

    public function test_reject(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/block-leave', ['tanggal_mulai' => '2026-08-03'])->json('data.id');

        $this->admin();
        $this->postJson("/api/block-leave/{$id}/reject")->assertOk();
    }

    public function test_cannot_create_on_weekend(): void
    {
        $this->pegawai();
        $r = $this->postJson('/api/block-leave', ['tanggal_mulai' => '2026-08-08']);
        $r->assertStatus(422);
    }

    public function test_insufficient_balance(): void
    {
        $user = $this->pegawai();
        $saldo = \App\Models\SaldoCuti::firstOrCreate(
            ['id_user' => $user->id, 'tahun' => 2026],
            ['saldo_awal' => 12, 'terpakai' => 0, 'penyesuaian' => 0, 'sisa' => 12]
        );
        $saldo->update(['sisa' => 2, 'terpakai' => 10]);

        $r = $this->postJson('/api/block-leave', ['tanggal_mulai' => '2026-08-03']);
        $r->assertStatus(422)->assertJsonFragment(['message' => 'Saldo cuti tidak mencukupi (minimum 5 hari).']);
    }
}
