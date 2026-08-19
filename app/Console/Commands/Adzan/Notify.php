<?php

namespace App\Console\Commands\Adzan;

use App\Models\PrayerTime;
use App\Models\User;
use App\Services\PushService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class Notify extends Command
{
    protected $signature = 'adzan:notify';
    protected $description = 'Cek jadwal sholat, kirim push notif kalau ada sholat yang jatuh menit ini';

    private array $labels = [
        'fajr' => 'Subuh',
        'dhuhr' => 'Dzuhur',
        'asr' => 'Ashar',
        'maghrib' => 'Maghrib',
        'isha' => 'Isya',
    ];

    private array $emojis = [
        'fajr' => '🌅',
        'dhuhr' => '☀️',
        'asr' => '🌤️',
        'maghrib' => '🌇',
        'isha' => '🌙',
    ];

    public function handle(PushService $push): int
    {
        $now = Carbon::now('Asia/Jakarta');
        $hm = $now->format('H:i');

        $rows = PrayerTime::whereDate('tanggal', $now->toDateString())->get();
        $sent = 0;

        foreach ($rows as $row) {
            foreach (array_keys($this->labels) as $key) {
                $t = substr((string) $row->{$key}, 0, 5);
                if ($t !== $hm) continue;

                $users = User::where('id_kantor', $row->id_kantor)
                    ->where('status', 'aktif')
                    ->where('adzan_notif', true)
                    ->get(['id']);

                foreach ($users as $u) {
                    $push->sendToUser(
                        $u->id,
                        $this->emojis[$key] . ' Waktu Sholat ' . $this->labels[$key],
                        'Sudah masuk waktu ' . $this->labels[$key] . ' (' . substr($t, 0, 5) . ' WIB). Semoga khusyuk. 🤲',
                        '/',
                    );
                    $sent++;
                }
            }
        }

        if ($sent > 0) {
            $this->info("Adzan {$hm} — {$sent} notif terkirim.");
        }
        return self::SUCCESS;
    }
}
