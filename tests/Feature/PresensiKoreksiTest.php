<?php

namespace Tests\Feature;

use App\Models\Kantor;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class PresensiKoreksiTest extends TestCase
{
    use SeedsDatabase;

    private function createPresensi(): int
    {
        $kantor = Kantor::where('kode', 'KP')->first();
        $r = $this->postJson('/api/presensi/clock-in', [
            'latitude' => $kantor->latitude,
            'longitude' => $kantor->longitude,
            'akurasi' => 10,
            'foto' => 'data:image/jpeg;base64,/9j/test',
            'device_id' => 'test-device-001',
        ]);
        return $r->json('data.id');
    }

    public function test_create_koreksi(): void
    {
        $this->pegawai();
        $presensiId = $this->createPresensi();

        $r = $this->postJson('/api/presensi/koreksi', [
            'id_presensi' => $presensiId,
            'tanggal' => now()->toDateString(),
            'jam_masuk_koreksi' => '07:30',
            'jam_keluar_koreksi' => '17:00',
            'alasan' => 'Lupa clock in',
        ]);
        $r->assertStatus(201)->assertJsonPath('data.status', 'menunggu');
    }

    public function test_list(): void
    {
        $this->pegawai();
        $this->getJson('/api/presensi/koreksi')->assertOk();
    }

    public function test_approve_applies_correction(): void
    {
        $this->pegawai();
        $presensiId = $this->createPresensi();
        $koreksiId = $this->postJson('/api/presensi/koreksi', [
            'id_presensi' => $presensiId,
            'tanggal' => now()->toDateString(),
            'jam_masuk_koreksi' => '07:30',
            'alasan' => 'Koreksi masuk',
        ])->json('data.id');

        $this->admin();
        $this->postJson("/api/presensi/koreksi/{$koreksiId}/approve")
             ->assertOk()->assertJsonPath('data.status', 'disetujui');
    }

    public function test_reject(): void
    {
        $this->pegawai();
        $presensiId = $this->createPresensi();
        $koreksiId = $this->postJson('/api/presensi/koreksi', [
            'id_presensi' => $presensiId,
            'tanggal' => now()->toDateString(),
            'jam_masuk_koreksi' => '07:30',
            'alasan' => 'Koreksi masuk',
        ])->json('data.id');

        $this->admin();
        $this->postJson("/api/presensi/koreksi/{$koreksiId}/reject")
             ->assertOk()->assertJsonPath('data.status', 'ditolak');
    }

    public function test_validation(): void
    {
        $this->pegawai();
        $this->postJson('/api/presensi/koreksi', [])->assertStatus(422);
    }
}
