<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lembur extends Model
{
    protected $table = 't_lembur';

    protected $fillable = [
        'id_user', 'tanggal', 'jam_mulai_rencana', 'jam_selesai_rencana',
        'jam_mulai_aktual', 'jam_selesai_aktual',
        'durasi_rencana', 'durasi_aktual',
        'uraian_pekerjaan', 'hasil_pekerjaan', 'lampiran',
        'status', 'approval_snapshot',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jam_mulai_aktual' => 'datetime',
        'jam_selesai_aktual' => 'datetime',
        'durasi_rencana' => 'decimal:2',
        'durasi_aktual' => 'decimal:2',
        'approval_snapshot' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
