<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Direktorat extends Model
{
    protected $table = 'm_direktorat';

    protected $fillable = [
        'id_entitas', 'kode', 'nama', 'status',
    ];

    public function entitas(): BelongsTo
    {
        return $this->belongsTo(Entitas::class, 'id_entitas');
    }

    public function divisi(): HasMany
    {
        return $this->hasMany(Divisi::class, 'id_direktorat');
    }
}
