<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrayerTime extends Model
{
    protected $table = 'prayer_times';

    protected $fillable = ['id_kantor', 'tanggal', 'fajr', 'dhuhr', 'asr', 'maghrib', 'isha'];

    protected $casts = ['tanggal' => 'date'];
}
