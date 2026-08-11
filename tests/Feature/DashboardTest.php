<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class DashboardTest extends TestCase
{
    use SeedsDatabase;

    public function test_employee_dashboard(): void
    {
        $this->pegawai();
        $r = $this->getJson('/api/dashboard');
        $r->assertOk()->assertJsonStructure(['data']);
    }

    public function test_supervisor_dashboard(): void
    {
        $this->supervisor();
        $r = $this->getJson('/api/dashboard/supervisor');
        $r->assertOk()->assertJsonStructure(['data']);
    }

    public function test_hr_dashboard(): void
    {
        $this->hr();
        $r = $this->getJson('/api/dashboard/hr');
        $r->assertOk()->assertJsonStructure(['data']);
    }

    public function test_pegawai_cannot_see_supervisor_dashboard(): void
    {
        $this->pegawai();
        $this->getJson('/api/dashboard/supervisor')->assertStatus(403);
    }

    public function test_pegawai_cannot_see_hr_dashboard(): void
    {
        $this->pegawai();
        $this->getJson('/api/dashboard/hr')->assertStatus(403);
    }
}
