<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jadwal extends Model
{
    protected $table = 'm_jadwal';

    protected $fillable = [
        'kode', 'nama', 'tipe', 'status',
    ];

    public function shift(): HasMany
    {
        return $this->hasMany(Shift::class, 'id_jadwal');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_jadwal');
    }
}
