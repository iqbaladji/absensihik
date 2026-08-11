<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresensiKoreksi extends Model
{
    protected $table = 't_presensi_koreksi';

    protected $fillable = [
        'id_user', 'id_presensi', 'tanggal',
        'jam_masuk_koreksi', 'jam_keluar_koreksi',
        'alasan', 'lampiran', 'status',
        'id_approver', 'waktu_approval', 'catatan_approval',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'waktu_approval' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function presensi(): BelongsTo
    {
        return $this->belongsTo(Presensi::class, 'id_presensi');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_approver');
    }
}
