<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pengumuman extends Model
{
    protected $table = 't_pengumuman';

    protected $fillable = [
        'id_user', 'id_jenis', 'judul', 'isi', 'lampiran',
        'prioritas', 'wajib_konfirmasi', 'target_tipe', 'target_ids',
        'status', 'published_at',
    ];

    protected $casts = [
        'wajib_konfirmasi' => 'boolean',
        'target_ids' => 'array',
        'published_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function jenis(): BelongsTo
    {
        return $this->belongsTo(JenisPengumuman::class, 'id_jenis');
    }

    public function penerima(): HasMany
    {
        return $this->hasMany(PengumumanPenerima::class, 'id_pengumuman');
    }
}
