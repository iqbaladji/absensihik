<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Divisi extends Model
{
    protected $table = 'm_divisi';

    protected $fillable = [
        'id_direktorat', 'kode', 'nama', 'status',
    ];

    public function direktorat(): BelongsTo
    {
        return $this->belongsTo(Direktorat::class, 'id_direktorat');
    }

    public function departemen(): HasMany
    {
        return $this->hasMany(Departemen::class, 'id_divisi');
    }
}
