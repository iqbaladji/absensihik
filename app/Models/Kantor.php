<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kantor extends Model
{
    protected $table = 'm_kantor';

    protected $fillable = [
        'id_entitas', 'kode', 'nama', 'tipe', 'alamat', 'telepon',
        'latitude', 'longitude', 'radius', 'zona_waktu', 'status',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'radius' => 'integer',
    ];

    public function entitas(): BelongsTo
    {
        return $this->belongsTo(Entitas::class, 'id_entitas');
    }

    public function unitKerja(): HasMany
    {
        return $this->hasMany(UnitKerja::class, 'id_kantor');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_kantor');
    }
}
