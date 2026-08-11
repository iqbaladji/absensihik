<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class AuthTest extends TestCase
{
    use SeedsDatabase;

    public function test_login_success(): void
    {
        $r = $this->postJson('/api/auth/login', ['username' => 'admin', 'password' => 'password']);
        $r->assertOk()
          ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'role']]]);
    }

    public function test_login_wrong_password(): void
    {
        $r = $this->postJson('/api/auth/login', ['username' => 'admin', 'password' => 'wrong']);
        $r->assertStatus(401)->assertJson(['message' => 'Username atau password salah.']);
    }

    public function test_login_nonexistent_user(): void
    {
        $r = $this->postJson('/api/auth/login', ['username' => 'nobody', 'password' => 'test']);
        $r->assertStatus(401);
    }

    public function test_login_validation(): void
    {
        $this->postJson('/api/auth/login', [])->assertStatus(422);
    }

    public function test_login_inactive_user(): void
    {
        $user = $this->user('budi');
        $user->update(['status' => 'nonaktif']);

        $r = $this->postJson('/api/auth/login', ['username' => 'budi', 'password' => 'password']);
        $r->assertStatus(403)->assertJson(['message' => 'Akun tidak aktif. Hubungi administrator.']);
    }

    public function test_me_authenticated(): void
    {
        $this->admin();
        $r = $this->getJson('/api/auth/me');
        $r->assertOk()->assertJsonPath('data.username', 'admin');
    }

    public function test_me_unauthenticated(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_logout(): void
    {
        $this->admin();
        $this->postJson('/api/auth/logout')->assertOk();
    }

    public function test_change_pin(): void
    {
        $this->admin();
        $r = $this->postJson('/api/auth/change-pin', [
            'pin_lama' => 'anything',
            'pin_baru' => '123456',
            'pin_baru_confirmation' => '123456',
        ]);
        $r->assertOk();

        $r2 = $this->postJson('/api/auth/change-pin', [
            'pin_lama' => '123456',
            'pin_baru' => '654321',
            'pin_baru_confirmation' => '654321',
        ]);
        $r2->assertOk();
    }

    public function test_change_pin_wrong_old(): void
    {
        $user = $this->admin();
        $user->update(['pin_payslip' => bcrypt('111111')]);

        $r = $this->postJson('/api/auth/change-pin', [
            'pin_lama' => '999999',
            'pin_baru' => '123456',
            'pin_baru_confirmation' => '123456',
        ]);
        $r->assertStatus(422)->assertJson(['message' => 'PIN lama salah.']);
    }

    public function test_each_role_can_login(): void
    {
        $usernames = ['admin', 'budi', 'siti', 'dewi', 'andi', 'hendra', 'rina'];
        foreach ($usernames as $u) {
            $r = $this->postJson('/api/auth/login', ['username' => $u, 'password' => 'password']);
            $r->assertOk();
        }
    }
}
