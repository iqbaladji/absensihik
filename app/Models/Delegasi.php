<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delegasi extends Model
{
    protected $table = 'm_delegasi';

    protected $fillable = [
        'id_dari', 'id_kepada', 'tanggal_mulai', 'tanggal_selesai',
        'modul', 'alasan', 'is_aktif',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'is_aktif' => 'boolean',
    ];

    public function dari(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_dari');
    }

    public function kepada(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_kepada');
    }
}
