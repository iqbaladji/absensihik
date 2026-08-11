<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KalenderKerja extends Model
{
    protected $table = 'm_kalender_kerja';

    protected $fillable = [
        'tanggal', 'is_hari_kerja', 'id_hari_libur', 'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'is_hari_kerja' => 'boolean',
    ];

    public function hariLibur(): BelongsTo
    {
        return $this->belongsTo(HariLibur::class, 'id_hari_libur');
    }
}
