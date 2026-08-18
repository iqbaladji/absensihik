<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reminder absen — hanya hari kerja (Senin–Jumat), timezone Asia/Jakarta
Schedule::command('presensi:reminder pagi')
    ->weekdays()->timezone('Asia/Jakarta')->dailyAt('07:45');

Schedule::command('presensi:reminder telat')
    ->weekdays()->timezone('Asia/Jakarta')->dailyAt('08:15');

Schedule::command('presensi:reminder sore')
    ->weekdays()->timezone('Asia/Jakarta')->dailyAt('16:55');
