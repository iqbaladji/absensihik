<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CutiMelahirkan extends Model
{
    protected $table = 't_cuti_melahirkan';

    protected $fillable = [
        'id_user', 'tanggal_mulai', 'tanggal_selesai',
        'jumlah_hari', 'tipe', 'catatan', 'lampiran', 'status', 'approval_snapshot',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'jumlah_hari' => 'integer',
        'approval_snapshot' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
