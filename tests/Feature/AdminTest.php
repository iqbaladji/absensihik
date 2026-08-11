<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class AdminTest extends TestCase
{
    use SeedsDatabase;

    public function test_list_users(): void
    {
        $this->admin();
        $r = $this->getJson('/api/admin/users');
        $r->assertOk()->assertJsonStructure(['data']);
    }

    public function test_show_user(): void
    {
        $this->admin();
        $users = $this->getJson('/api/admin/users')->json('data');
        $this->getJson("/api/admin/users/{$users[0]['id']}")->assertOk();
    }

    public function test_create_user(): void
    {
        $this->admin();
        $r = $this->postJson('/api/admin/users', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@bprshik.co.id',
            'nip' => '200001',
            'password' => 'password',
            'id_role' => 2,
            'id_kantor' => 1,
            'status' => 'aktif',
        ]);
        $r->assertStatus(201);
    }

    public function test_update_user(): void
    {
        $this->admin();
        $user = $this->getJson('/api/admin/users')->json('data.1');
        $this->putJson("/api/admin/users/{$user['id']}", array_merge($user, ['name' => 'Updated Name']))
             ->assertOk();
    }

    public function test_reset_password(): void
    {
        $this->admin();
        $user = $this->user('budi');
        $this->postJson("/api/admin/users/{$user->id}/reset-password")->assertOk();
    }

    public function test_list_roles(): void
    {
        $this->admin();
        $r = $this->getJson('/api/admin/roles');
        $r->assertOk();
        $this->assertGreaterThanOrEqual(7, count($r->json('data')));
    }

    public function test_show_role(): void
    {
        $this->admin();
        $roles = $this->getJson('/api/admin/roles')->json('data');
        $this->getJson("/api/admin/roles/{$roles[0]['id']}")->assertOk();
    }

    public function test_audit_trail(): void
    {
        $this->admin();
        $this->postJson('/api/auth/login', ['username' => 'budi', 'password' => 'password']);
        $r = $this->getJson('/api/admin/audit-trail');
        $r->assertOk();
    }

    public function test_pegawai_cannot_access_admin(): void
    {
        $this->pegawai();
        $this->getJson('/api/admin/users')->assertStatus(403);
        $this->getJson('/api/admin/roles')->assertStatus(403);
    }
}
