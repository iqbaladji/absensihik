<?php

namespace Tests\Feature;

use App\Models\Payslip;
use App\Models\PayslipPeriode;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class PayslipTest extends TestCase
{
    use SeedsDatabase;

    private function seedPayslip(int $userId): array
    {
        $periode = PayslipPeriode::create([
            'periode' => '2026-07',
            'nama' => 'Juli 2026',
            'status' => 'published',
            'published_at' => now(),
            'id_user_publish' => 1,
        ]);

        $payslip = Payslip::create([
            'id_user' => $userId,
            'id_periode' => $periode->id,
            'gaji_bruto' => 10000000,
            'total_potongan' => 1500000,
            'gaji_netto' => 8500000,
            'komponen' => json_encode([
                ['nama' => 'Gaji Pokok', 'tipe' => 'pendapatan', 'nominal' => 8000000],
                ['nama' => 'Tunjangan', 'tipe' => 'pendapatan', 'nominal' => 2000000],
                ['nama' => 'BPJS', 'tipe' => 'potongan', 'nominal' => 1500000],
            ]),
        ]);

        return ['periode' => $periode, 'payslip' => $payslip];
    }

    public function test_verify_pin(): void
    {
        $user = $this->pegawai();
        $user->update(['pin_payslip' => Hash::make('123456')]);

        $this->postJson('/api/payslip/verify-pin', ['pin' => '123456'])->assertOk();
    }

    public function test_verify_pin_wrong(): void
    {
        $user = $this->pegawai();
        $user->update(['pin_payslip' => Hash::make('123456')]);

        $this->postJson('/api/payslip/verify-pin', ['pin' => '999999'])->assertStatus(422);
    }

    public function test_list_payslips(): void
    {
        $user = $this->pegawai();
        $this->seedPayslip($user->id);

        $r = $this->getJson('/api/payslip');
        $r->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_show_payslip(): void
    {
        $user = $this->pegawai();
        $data = $this->seedPayslip($user->id);

        $r = $this->getJson("/api/payslip/{$data['payslip']->id}");
        $r->assertOk();
        $this->assertEquals(10000000, (float) $r->json('data.gaji_bruto'));
        $this->assertEquals(8500000, (float) $r->json('data.gaji_netto'));
    }

    public function test_cannot_see_others_payslip(): void
    {
        $admin = $this->admin();
        $data = $this->seedPayslip($admin->id);

        $this->pegawai();
        $this->getJson("/api/payslip/{$data['payslip']->id}")->assertStatus(403);
    }

    public function test_unauthenticated(): void
    {
        $this->getJson('/api/payslip')->assertStatus(401);
    }
}
