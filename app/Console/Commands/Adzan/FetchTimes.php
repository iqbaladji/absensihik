<?php

namespace App\Console\Commands\Adzan;

use App\Models\Kantor;
use App\Models\PrayerTime;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchTimes extends Command
{
    protected $signature = 'adzan:fetch {--date= : YYYY-MM-DD (default: today)}';
    protected $description = 'Fetch jadwal sholat harian per kantor dari Aladhan (Kemenag method)';

    public function handle(): int
    {
        $tanggal = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
        $kantors = Kantor::whereNotNull('latitude')->whereNotNull('longitude')->get();
        if ($kantors->isEmpty()) {
            $this->warn('Tidak ada kantor dengan koordinat GPS. Set lat/lng terlebih dahulu.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($kantors as $k) {
            try {
                $res = Http::timeout(15)->get('https://api.aladhan.com/v1/timings/' . $tanggal->format('d-m-Y'), [
                    'latitude' => (float) $k->latitude,
                    'longitude' => (float) $k->longitude,
                    'method' => 20, // Kemenag Indonesia
                ]);
                if (! $res->successful()) {
                    $this->warn("Kantor {$k->kode}: gagal fetch (HTTP {$res->status()})");
                    continue;
                }
                $t = $res->json('data.timings') ?? [];
                if (empty($t['Fajr'])) {
                    $this->warn("Kantor {$k->kode}: data timings kosong.");
                    continue;
                }
                PrayerTime::updateOrCreate(
                    ['id_kantor' => $k->id, 'tanggal' => $tanggal->toDateString()],
                    [
                        'fajr' => $this->stripTz($t['Fajr']),
                        'dhuhr' => $this->stripTz($t['Dhuhr']),
                        'asr' => $this->stripTz($t['Asr']),
                        'maghrib' => $this->stripTz($t['Maghrib']),
                        'isha' => $this->stripTz($t['Isha']),
                    ],
                );
                $count++;
            } catch (\Throwable $e) {
                $this->warn("Kantor {$k->kode}: {$e->getMessage()}");
            }
        }

        $this->info("Jadwal {$tanggal->toDateString()} disimpan untuk {$count} kantor.");
        return self::SUCCESS;
    }

    private function stripTz(string $hm): string
    {
        // Aladhan sometimes returns "04:12 (WIB)" — normalize to HH:MM:SS
        return substr(explode(' ', $hm)[0], 0, 5) . ':00';
    }
}
