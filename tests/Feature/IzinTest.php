<?php

namespace Tests\Feature;

use App\Models\JenisIzin;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class IzinTest extends TestCase
{
    use SeedsDatabase;

    private function validData(): array
    {
        $jenisId = JenisIzin::where('kode', 'IZN')->value('id');
        return [
            'id_jenis_izin' => $jenisId,
            'tanggal_mulai' => '2026-08-10',
            'tanggal_selesai' => '2026-08-10',
            'alasan' => 'Keperluan pribadi',
        ];
    }

    public function test_create(): void
    {
        $this->pegawai();
        $this->postJson('/api/izin', $this->validData())
             ->assertStatus(201)->assertJsonPath('data.status', 'menunggu');
    }

    public function test_list(): void
    {
        $this->pegawai();
        $this->postJson('/api/izin', $this->validData());
        $this->getJson('/api/izin')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_approve(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/izin', $this->validData())->json('data.id');

        $this->admin();
        $this->postJson("/api/izin/{$id}/approve")->assertOk()->assertJsonPath('data.status', 'disetujui');
    }

    public function test_reject(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/izin', $this->validData())->json('data.id');

        $this->admin();
        $this->postJson("/api/izin/{$id}/reject", ['catatan' => 'Tidak bisa'])->assertOk();
    }

    public function test_cancel(): void
    {
        $user = $this->admin();
        $id = $this->postJson('/api/izin', $this->validData())->json('data.id');
        $this->postJson("/api/izin/{$id}/cancel")->assertOk();
    }

    public function test_update_pending(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/izin', $this->validData())->json('data.id');
        $this->putJson("/api/izin/{$id}", array_merge($this->validData(), ['alasan' => 'Updated']))
             ->assertOk();
    }

    public function test_show(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/izin', $this->validData())->json('data.id');
        $this->getJson("/api/izin/{$id}")->assertOk()->assertJsonPath('data.alasan', 'Keperluan pribadi');
    }

    public function test_validation_missing_fields(): void
    {
        $this->pegawai();
        $this->postJson('/api/izin', [])->assertStatus(422);
    }
}
