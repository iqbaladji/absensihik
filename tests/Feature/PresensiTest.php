<?php

namespace Tests\Feature;

use App\Models\Kantor;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class PresensiTest extends TestCase
{
    use SeedsDatabase;

    private function clockInData(): array
    {
        $kantor = Kantor::where('kode', 'KP')->first();
        return [
            'latitude' => $kantor->latitude,
            'longitude' => $kantor->longitude,
            'akurasi' => 10,
            'foto' => 'data:image/jpeg;base64,/9j/test',
            'device_id' => 'test-device-001',
            'device_model' => 'PHPUnit',
        ];
    }

    public function test_clock_in(): void
    {
        $this->pegawai();
        $r = $this->postJson('/api/presensi/clock-in', $this->clockInData());
        $r->assertStatus(201)->assertJsonStructure(['data' => ['id', 'jam_masuk', 'status_masuk']]);
    }

    public function test_clock_out(): void
    {
        $this->pegawai();
        $this->postJson('/api/presensi/clock-in', $this->clockInData());

        $r = $this->postJson('/api/presensi/clock-out', $this->clockInData());
        $r->assertOk()->assertJsonStructure(['data' => ['jam_keluar']]);
    }

    public function test_today(): void
    {
        $this->pegawai();
        $this->getJson('/api/presensi/today')->assertOk();
    }

    public function test_riwayat(): void
    {
        $this->pegawai();
        $r = $this->getJson('/api/presensi/riwayat?dari=2026-01-01&sampai=2026-12-31');
        $r->assertOk();
    }

    public function test_tim(): void
    {
        $this->supervisor();
        $this->getJson('/api/presensi/tim')->assertOk();
    }

    public function test_verify(): void
    {
        $this->pegawai();
        $kantor = Kantor::where('kode', 'KP')->first();

        // Clock-in slightly outside radius but within GPS accuracy border
        $presensi = $this->postJson('/api/presensi/clock-in', [
            'latitude' => $kantor->latitude + 0.005,
            'longitude' => $kantor->longitude,
            'akurasi' => 1000,
            'foto' => 'data:image/jpeg;base64,/9j/test',
            'device_id' => 'test-device-001',
            'tipe' => 'dinas_luar',
        ])->json('data');

        if (! ($presensi['perlu_verifikasi'] ?? false)) {
            // Force it for testing
            \App\Models\Presensi::where('id', $presensi['id'])->update(['perlu_verifikasi' => true]);
        }

        $this->admin();
        $this->postJson("/api/presensi/{$presensi['id']}/verify", [
            'aksi' => 'approve',
            'catatan' => 'OK',
        ])->assertOk();
    }

    public function test_double_clock_in(): void
    {
        $this->pegawai();
        $this->postJson('/api/presensi/clock-in', $this->clockInData())->assertStatus(201);
        $this->postJson('/api/presensi/clock-in', $this->clockInData())->assertStatus(422);
    }

    public function test_unauthenticated(): void
    {
        $this->getJson('/api/presensi/today')->assertStatus(401);
    }

    public function test_clock_in_validation(): void
    {
        $this->pegawai();
        $this->postJson('/api/presensi/clock-in', [])->assertStatus(422);
    }
}
