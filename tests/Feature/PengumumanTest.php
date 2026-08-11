<?php

namespace Tests\Feature;

use App\Models\JenisPengumuman;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class PengumumanTest extends TestCase
{
    use SeedsDatabase;

    private function validData(): array
    {
        return [
            'judul' => 'Test Pengumuman',
            'isi' => 'Isi pengumuman penting',
            'id_jenis' => JenisPengumuman::where('kode', 'UMM')->value('id'),
            'prioritas' => 'normal',
            'wajib_konfirmasi' => false,
            'target_tipe' => 'semua',
            'target_ids' => [],
        ];
    }

    public function test_create_draft(): void
    {
        $this->admin();
        $r = $this->postJson('/api/pengumuman', $this->validData());
        $r->assertStatus(201)->assertJsonPath('data.status', 'draft');
    }

    public function test_list(): void
    {
        $this->admin();
        $this->postJson('/api/pengumuman', $this->validData());
        $this->getJson('/api/pengumuman')->assertOk();
    }

    public function test_show(): void
    {
        $this->admin();
        $id = $this->postJson('/api/pengumuman', $this->validData())->json('data.id');
        $this->getJson("/api/pengumuman/{$id}")
             ->assertOk()
             ->assertJsonPath('data.judul', 'Test Pengumuman');
    }

    public function test_update_draft(): void
    {
        $this->admin();
        $id = $this->postJson('/api/pengumuman', $this->validData())->json('data.id');
        $this->putJson("/api/pengumuman/{$id}", array_merge($this->validData(), ['judul' => 'Updated']))
             ->assertOk()->assertJsonPath('data.judul', 'Updated');
    }

    public function test_publish(): void
    {
        $this->admin();
        $id = $this->postJson('/api/pengumuman', $this->validData())->json('data.id');
        $r = $this->postJson("/api/pengumuman/{$id}/publish");
        $r->assertOk()->assertJsonPath('data.status', 'published');
        $this->assertNotNull($r->json('data.published_at'));
    }

    public function test_retract(): void
    {
        $this->admin();
        $id = $this->postJson('/api/pengumuman', $this->validData())->json('data.id');
        $this->postJson("/api/pengumuman/{$id}/publish");
        $this->postJson("/api/pengumuman/{$id}/retract")->assertOk();
    }

    public function test_confirm_read(): void
    {
        $this->admin();
        $data = array_merge($this->validData(), ['wajib_konfirmasi' => true]);
        $id = $this->postJson('/api/pengumuman', $data)->json('data.id');
        $this->postJson("/api/pengumuman/{$id}/publish");

        $this->pegawai();
        $this->postJson("/api/pengumuman/{$id}/confirm")->assertOk();
    }

    public function test_tracking(): void
    {
        $this->admin();
        $id = $this->postJson('/api/pengumuman', $this->validData())->json('data.id');
        $this->postJson("/api/pengumuman/{$id}/publish");
        $r = $this->getJson("/api/pengumuman/{$id}/tracking");
        $r->assertOk()->assertJsonStructure(['data' => ['total_penerima']]);
    }

    public function test_all_priorities(): void
    {
        $this->admin();
        foreach (['rendah', 'normal', 'tinggi', 'urgent'] as $p) {
            $data = array_merge($this->validData(), ['prioritas' => $p, 'judul' => "Prioritas $p"]);
            $this->postJson('/api/pengumuman', $data)->assertStatus(201);
        }
    }

    public function test_target_types(): void
    {
        $this->admin();
        foreach (['semua', 'kantor', 'unit', 'jabatan'] as $t) {
            $data = array_merge($this->validData(), ['target_tipe' => $t, 'judul' => "Target $t"]);
            $this->postJson('/api/pengumuman', $data)->assertStatus(201);
        }
    }

    public function test_payroll_cannot_access_pengumuman(): void
    {
        $this->payroll();
        $this->getJson('/api/pengumuman')->assertStatus(403);
    }

    public function test_validation(): void
    {
        $this->admin();
        $this->postJson('/api/pengumuman', [])->assertStatus(422);
    }

    public function test_delete_draft(): void
    {
        $this->admin();
        $id = $this->postJson('/api/pengumuman', $this->validData())->json('data.id');
        $this->deleteJson("/api/pengumuman/{$id}")->assertOk();
    }
}
