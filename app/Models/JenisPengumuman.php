<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisPengumuman extends Model
{
    protected $table = 'm_jenis_pengumuman';

    protected $fillable = [
        'kode', 'nama', 'status',
    ];

    public function pengumuman(): HasMany
    {
        return $this->hasMany(Pengumuman::class, 'id_jenis');
    }
}
