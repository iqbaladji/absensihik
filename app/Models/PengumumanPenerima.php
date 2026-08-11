<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengumumanPenerima extends Model
{
    protected $table = 't_pengumuman_penerima';

    protected $fillable = [
        'id_pengumuman', 'id_user', 'dibaca_pada', 'dikonfirmasi_pada',
    ];

    protected $casts = [
        'dibaca_pada' => 'datetime',
        'dikonfirmasi_pada' => 'datetime',
    ];

    public function pengumuman(): BelongsTo
    {
        return $this->belongsTo(Pengumuman::class, 'id_pengumuman');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
