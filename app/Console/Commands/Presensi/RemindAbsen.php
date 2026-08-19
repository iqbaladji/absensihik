<?php

namespace App\Console\Commands\Presensi;

use App\Models\Notifikasi;
use App\Models\Presensi;
use App\Models\User;
use App\Services\PushService;
use Illuminate\Console\Command;

class RemindAbsen extends Command
{
    protected $signature = 'presensi:reminder {slot : pagi|telat|sore}';
    protected $description = 'Kirim reminder absen ke pegawai via in-app + web push';

    public function handle(PushService $push): int
    {
        $slot = $this->argument('slot');

        [$judul, $pesan, $filter] = match ($slot) {
            'pagi' => [
                'Selamat pagi! ☀️',
                'Jangan lupa absen masuk sebelum jam 08:00.',
                fn ($q) => $q,
            ],
            'telat' => [
                '⚠️ Anda belum absen',
                'Segera clock in sebelum status jadi alpha.',
                fn ($q) => $q->whereDoesntHave('presensi', fn ($p) => $p->whereDate('tanggal', today())),
            ],
            'sore' => [
                '🌇 Waktunya clock out',
                'Jangan lupa clock out sebelum meninggalkan kantor.',
                fn ($q) => $q->whereHas('presensi', fn ($p) => $p->whereDate('tanggal', today())->whereNull('waktu_pulang')),
            ],
            default => [null, null, null],
        };

        if (! $judul) {
            $this->error('Slot tidak dikenal. Gunakan: pagi, telat, sore.');
            return self::FAILURE;
        }

        $q = User::where('status', 'aktif')
            ->whereHas('role', fn ($r) => $r->where('slug', 'pegawai'));
        $q = $filter($q);
        $users = $q->get();

        $sent = 0;
        foreach ($users as $user) {
            Notifikasi::create([
                'id_user' => $user->id,
                'judul' => $judul,
                'pesan' => $pesan,
                'tipe' => 'presensi',
                'url' => '/presensi',
            ]);
            $push->sendToUser($user->id, $judul, $pesan, '/presensi');
            $sent++;
        }

        $this->info("Reminder [$slot] dikirim ke $sent pegawai.");
        return self::SUCCESS;
    }
}
