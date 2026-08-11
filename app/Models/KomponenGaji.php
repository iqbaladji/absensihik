<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KomponenGaji extends Model
{
    protected $table = 'm_komponen_gaji';

    protected $fillable = [
        'kode', 'nama', 'tipe', 'urutan', 'status',
    ];

    protected $casts = [
        'urutan' => 'integer',
    ];
}
