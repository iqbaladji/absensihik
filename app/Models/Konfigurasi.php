<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Konfigurasi extends Model
{
    protected $table = 'm_konfigurasi';

    protected $fillable = [
        'kunci', 'nilai', 'tipe', 'grup', 'deskripsi',
    ];
}
