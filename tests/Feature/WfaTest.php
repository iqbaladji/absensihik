<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class WfaTest extends TestCase
{
    use SeedsDatabase;

    private array $validData = [
        'tanggal_mulai' => '2026-08-10',
        'tanggal_selesai' => '2026-08-12',
        'lokasi' => 'Bandung - Coworking',
        'alasan' => 'Meeting klien',
    ];

    public function test_create(): void
    {
        $this->pegawai();
        $this->postJson('/api/wfa', $this->validData)
             ->assertStatus(201)->assertJsonPath('data.status', 'menunggu');
    }

    public function test_list(): void
    {
        $this->pegawai();
        $this->postJson('/api/wfa', $this->validData);
        $this->getJson('/api/wfa')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_full_approval_flow(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/wfa', $this->validData)->json('data.id');

        $this->admin();
        $this->postJson("/api/wfa/{$id}/approve")->assertOk();

        $this->postJson("/api/wfa/{$id}/approve")->assertStatus(422);
    }

    public function test_reject(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/wfa', $this->validData)->json('data.id');

        $this->admin();
        $this->postJson("/api/wfa/{$id}/reject")->assertOk();
    }

    public function test_update_delete(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/wfa', $this->validData)->json('data.id');
        $this->putJson("/api/wfa/{$id}", array_merge($this->validData, ['lokasi' => 'Yogya']))->assertOk();
        $this->deleteJson("/api/wfa/{$id}")->assertOk();
    }

    public function test_validation(): void
    {
        $this->pegawai();
        $this->postJson('/api/wfa', [])->assertStatus(422);
    }
}
