<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class WfhTest extends TestCase
{
    use SeedsDatabase;

    private array $validData = [
        'tanggal_mulai' => '2026-08-10',
        'tanggal_selesai' => '2026-08-10',
        'alasan' => 'Anak sakit',
    ];

    public function test_pegawai_create(): void
    {
        $this->pegawai();
        $this->postJson('/api/wfh', $this->validData)->assertStatus(201);
    }

    public function test_list(): void
    {
        $this->pegawai();
        $this->postJson('/api/wfh', $this->validData);
        $this->getJson('/api/wfh')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_approve(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/wfh', $this->validData)->json('data.id');

        $this->admin();
        $this->postJson("/api/wfh/{$id}/approve")->assertOk()->assertJsonPath('data.status', 'disetujui');
    }

    public function test_reject(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/wfh', $this->validData)->json('data.id');

        $this->admin();
        $this->postJson("/api/wfh/{$id}/reject", ['catatan' => 'Tidak diizinkan'])
             ->assertOk()->assertJsonPath('data.status', 'ditolak');
    }

    public function test_update_and_delete_pending(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/wfh', $this->validData)->json('data.id');
        $this->putJson("/api/wfh/{$id}", array_merge($this->validData, ['alasan' => 'Updated']))->assertOk();
        $this->deleteJson("/api/wfh/{$id}")->assertOk();
    }

    public function test_validation(): void
    {
        $this->pegawai();
        $this->postJson('/api/wfh', [])->assertStatus(422);
    }
}
