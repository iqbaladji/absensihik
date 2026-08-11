<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class DinasLuarTest extends TestCase
{
    use SeedsDatabase;

    private array $validData = [
        'tanggal_mulai' => '2026-08-10',
        'tanggal_selesai' => '2026-08-11',
        'tujuan' => 'Jakarta',
        'keperluan' => 'Meeting klien',
    ];

    public function test_pegawai_can_create(): void
    {
        $this->pegawai();
        $r = $this->postJson('/api/dinas-luar', $this->validData);
        $r->assertStatus(201)->assertJsonPath('data.status', 'menunggu');
    }

    public function test_list_dinas_luar(): void
    {
        $this->pegawai();
        $this->postJson('/api/dinas-luar', $this->validData);
        $r = $this->getJson('/api/dinas-luar');
        $r->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_show_detail(): void
    {
        $this->pegawai();
        $created = $this->postJson('/api/dinas-luar', $this->validData)->json('data');
        $r = $this->getJson("/api/dinas-luar/{$created['id']}");
        $r->assertOk()->assertJsonPath('data.tujuan', 'Jakarta');
    }

    public function test_update_pending(): void
    {
        $this->pegawai();
        $created = $this->postJson('/api/dinas-luar', $this->validData)->json('data');
        $r = $this->putJson("/api/dinas-luar/{$created['id']}", array_merge($this->validData, ['tujuan' => 'Surabaya']));
        $r->assertOk()->assertJsonPath('data.tujuan', 'Surabaya');
    }

    public function test_approve_flow(): void
    {
        $this->pegawai();
        $created = $this->postJson('/api/dinas-luar', $this->validData)->json('data');

        $this->admin();
        $r = $this->postJson("/api/dinas-luar/{$created['id']}/approve", ['catatan' => 'OK']);
        $r->assertOk()->assertJsonPath('data.status', 'disetujui');
    }

    public function test_reject_flow(): void
    {
        $this->pegawai();
        $created = $this->postJson('/api/dinas-luar', $this->validData)->json('data');

        $this->admin();
        $r = $this->postJson("/api/dinas-luar/{$created['id']}/reject", ['catatan' => 'Ditolak']);
        $r->assertOk()->assertJsonPath('data.status', 'ditolak');
    }

    public function test_cannot_update_after_approved(): void
    {
        $this->pegawai();
        $created = $this->postJson('/api/dinas-luar', $this->validData)->json('data');

        $this->admin();
        $this->postJson("/api/dinas-luar/{$created['id']}/approve");

        $this->pegawai();
        $r = $this->putJson("/api/dinas-luar/{$created['id']}", $this->validData);
        $r->assertStatus(422);
    }

    public function test_store_validation(): void
    {
        $this->pegawai();
        $this->postJson('/api/dinas-luar', [])->assertStatus(422);
    }

    public function test_cannot_approve_already_processed(): void
    {
        $this->pegawai();
        $created = $this->postJson('/api/dinas-luar', $this->validData)->json('data');

        $this->admin();
        $this->postJson("/api/dinas-luar/{$created['id']}/approve");
        $r = $this->postJson("/api/dinas-luar/{$created['id']}/approve");
        $r->assertStatus(422);
    }

    public function test_delete_pending(): void
    {
        $this->pegawai();
        $created = $this->postJson('/api/dinas-luar', $this->validData)->json('data');
        $this->deleteJson("/api/dinas-luar/{$created['id']}")->assertOk();
    }
}
