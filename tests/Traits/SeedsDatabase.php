<?php

namespace Tests\Traits;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

trait SeedsDatabase
{
    use RefreshDatabase;

    protected function seedOnce(): void
    {
        $this->seed(DatabaseSeeder::class);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedOnce();
    }

    protected function user(string $username): User
    {
        return User::where('username', $username)->firstOrFail();
    }

    protected function actAs(string $username): User
    {
        $user = $this->user($username);
        Sanctum::actingAs($user);
        return $user;
    }

    protected function admin(): User { return $this->actAs('admin'); }
    protected function pegawai(): User { return $this->actAs('budi'); }
    protected function supervisor(): User { return $this->actAs('siti'); }
    protected function hr(): User { return $this->actAs('dewi'); }
    protected function payroll(): User { return $this->actAs('andi'); }
    protected function manajemen(): User { return $this->actAs('hendra'); }
    protected function adminKantor(): User { return $this->actAs('rina'); }
}
