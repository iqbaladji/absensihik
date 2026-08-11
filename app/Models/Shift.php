<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    protected $table = 'm_shift';

    protected $fillable = [
        'id_jadwal', 'nama', 'hari', 'jam_masuk', 'jam_keluar',
        'toleransi_terlambat', 'toleransi_pulang_awal', 'is_libur',
    ];

    protected $casts = [
        'toleransi_terlambat' => 'integer',
        'toleransi_pulang_awal' => 'integer',
        'is_libur' => 'boolean',
    ];

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(Jadwal::class, 'id_jadwal');
    }
}
