<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisIzin extends Model
{
    protected $table = 'm_jenis_izin';

    protected $fillable = [
        'kode', 'nama', 'potong_cuti', 'maks_hari', 'perlu_lampiran', 'status',
    ];

    protected $casts = [
        'potong_cuti' => 'boolean',
        'maks_hari' => 'integer',
        'perlu_lampiran' => 'boolean',
    ];

    public function izin(): HasMany
    {
        return $this->hasMany(Izin::class, 'id_jenis_izin');
    }
}
