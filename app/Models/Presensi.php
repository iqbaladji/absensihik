<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Presensi extends Model
{
    protected $table = 't_presensi';

    protected $fillable = [
        'id_user', 'id_kantor', 'tanggal',
        'jam_masuk', 'jam_keluar',
        'lat_masuk', 'lng_masuk', 'accuracy_masuk', 'jarak_masuk',
        'lat_keluar', 'lng_keluar', 'accuracy_keluar', 'jarak_keluar',
        'foto_masuk', 'foto_keluar',
        'device_id', 'device_model',
        'tipe', 'status_masuk', 'status_keluar',
        'perlu_verifikasi', 'id_verifikator', 'waktu_verifikasi', 'catatan_verifikasi',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jam_masuk' => 'datetime',
        'jam_keluar' => 'datetime',
        'lat_masuk' => 'decimal:7',
        'lng_masuk' => 'decimal:7',
        'accuracy_masuk' => 'decimal:2',
        'jarak_masuk' => 'decimal:2',
        'lat_keluar' => 'decimal:7',
        'lng_keluar' => 'decimal:7',
        'accuracy_keluar' => 'decimal:2',
        'jarak_keluar' => 'decimal:2',
        'perlu_verifikasi' => 'boolean',
        'waktu_verifikasi' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function kantor(): BelongsTo
    {
        return $this->belongsTo(Kantor::class, 'id_kantor');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_verifikator');
    }

    public function koreksi(): HasMany
    {
        return $this->hasMany(PresensiKoreksi::class, 'id_presensi');
    }
}
