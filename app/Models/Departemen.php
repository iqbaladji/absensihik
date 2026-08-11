<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departemen extends Model
{
    protected $table = 'm_departemen';

    protected $fillable = [
        'id_divisi', 'kode', 'nama', 'status',
    ];

    public function divisi(): BelongsTo
    {
        return $this->belongsTo(Divisi::class, 'id_divisi');
    }

    public function unitKerja(): HasMany
    {
        return $this->hasMany(UnitKerja::class, 'id_departemen');
    }
}
