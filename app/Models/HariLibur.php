<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HariLibur extends Model
{
    protected $table = 'm_hari_libur';

    protected $fillable = [
        'tanggal', 'nama', 'tipe', 'is_recurring',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'is_recurring' => 'boolean',
    ];
}
