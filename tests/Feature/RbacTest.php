<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class RbacTest extends TestCase
{
    use SeedsDatabase;

    public function test_admin_has_full_access(): void
    {
        $this->admin();
        $this->getJson('/api/dashboard')->assertOk();
        $this->getJson('/api/dashboard/supervisor')->assertOk();
        $this->getJson('/api/dashboard/hr')->assertOk();
        $this->getJson('/api/master/kantor')->assertOk();
        $this->getJson('/api/admin/users')->assertOk();
        $this->getJson('/api/lembur')->assertOk();
        $this->getJson('/api/pengumuman')->assertOk();
    }

    public function test_pegawai_restricted(): void
    {
        $this->pegawai();
        $this->getJson('/api/dashboard')->assertOk();
        $this->getJson('/api/lembur')->assertOk();
        $this->getJson('/api/pengumuman')->assertOk();

        $this->getJson('/api/dashboard/supervisor')->assertStatus(403);
        $this->getJson('/api/dashboard/hr')->assertStatus(403);
        $this->getJson('/api/master/kantor')->assertStatus(403);
        $this->getJson('/api/admin/users')->assertStatus(403);

        $this->postJson('/api/pengumuman', [])->assertStatus(422);
    }

    public function test_supervisor_can_approve(): void
    {
        $this->pegawai();
        $id = $this->postJson('/api/dinas-luar', [
            'tanggal_mulai' => '2026-08-10',
            'tanggal_selesai' => '2026-08-11',
            'tujuan' => 'Jakarta',
            'keperluan' => 'Meeting',
        ])->json('data.id');

        $this->supervisor();
        $this->postJson("/api/dinas-luar/{$id}/approve")->assertOk();
    }

    public function test_pegawai_cannot_approve(): void
    {
        $user = $this->pegawai();
        $id = $this->postJson('/api/dinas-luar', [
            'tanggal_mulai' => '2026-08-10',
            'tanggal_selesai' => '2026-08-11',
            'tujuan' => 'Jakarta',
            'keperluan' => 'Meeting',
        ])->json('data.id');

        $this->actAs('budi');
        $this->postJson("/api/dinas-luar/{$id}/approve")->assertStatus(403);
    }

    public function test_hr_can_access_pengumuman(): void
    {
        $this->hr();
        $this->getJson('/api/pengumuman')->assertOk();
        $this->postJson('/api/pengumuman', [
            'judul' => 'HR Test',
            'isi' => 'Content',
            'id_jenis' => 1,
            'prioritas' => 'normal',
            'wajib_konfirmasi' => false,
            'target_tipe' => 'semua',
            'target_ids' => [],
        ])->assertStatus(201);
    }

    public function test_payroll_can_access_payslip_admin(): void
    {
        $this->payroll();
        $this->getJson('/api/payslip-admin/periode')->assertOk();
    }

    public function test_payroll_cannot_access_other_modules(): void
    {
        $this->payroll();
        $this->getJson('/api/master/kantor')->assertStatus(403);
        $this->getJson('/api/admin/users')->assertStatus(403);
    }

    public function test_manajemen_can_approve_and_view(): void
    {
        $this->manajemen();
        $this->getJson('/api/dashboard')->assertOk();
        $this->getJson('/api/dashboard/supervisor')->assertOk();
    }

    public function test_admin_kantor_can_verify_presensi(): void
    {
        $this->adminKantor();
        $this->getJson('/api/presensi/tim')->assertOk();
    }

    public function test_unauthenticated_rejected(): void
    {
        $this->getJson('/api/dashboard')->assertStatus(401);
        $this->getJson('/api/lembur')->assertStatus(401);
        $this->postJson('/api/dinas-luar', [])->assertStatus(401);
    }
}
