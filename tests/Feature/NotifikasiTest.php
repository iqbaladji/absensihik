<?php

namespace Tests\Feature;

use App\Models\Notifikasi;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class NotifikasiTest extends TestCase
{
    use SeedsDatabase;

    public function test_list(): void
    {
        $this->pegawai();
        $this->getJson('/api/notifikasi')->assertOk();
    }

    public function test_unread_count(): void
    {
        $user = $this->pegawai();

        Notifikasi::create([
            'id_user' => $user->id,
            'judul' => 'Test notif',
            'pesan' => 'Pesan test',
            'tipe' => 'info',
        ]);

        $r = $this->getJson('/api/notifikasi/unread-count');
        $r->assertOk()->assertJsonStructure(['data' => ['count']]);
    }

    public function test_mark_read(): void
    {
        $user = $this->pegawai();
        $notif = Notifikasi::create([
            'id_user' => $user->id,
            'judul' => 'Test',
            'pesan' => 'Content',
            'tipe' => 'info',
        ]);

        $this->postJson("/api/notifikasi/{$notif->id}/read")->assertOk();
    }

    public function test_mark_all_read(): void
    {
        $user = $this->pegawai();
        Notifikasi::create(['id_user' => $user->id, 'judul' => 'A', 'pesan' => 'B', 'tipe' => 'info']);
        Notifikasi::create(['id_user' => $user->id, 'judul' => 'C', 'pesan' => 'D', 'tipe' => 'info']);

        $this->postJson('/api/notifikasi/read-all')->assertOk();
    }
}
