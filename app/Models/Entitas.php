<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entitas extends Model
{
    protected $table = 'm_entitas';

    protected $fillable = [
        'kode', 'nama', 'alamat', 'telepon', 'status',
    ];

    public function direktorat(): HasMany
    {
        return $this->hasMany(Direktorat::class, 'id_entitas');
    }

    public function kantor(): HasMany
    {
        return $this->hasMany(Kantor::class, 'id_entitas');
    }
}
