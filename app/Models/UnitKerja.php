<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitKerja extends Model
{
    protected $table = 'm_unit_kerja';

    protected $fillable = [
        'id_departemen', 'id_kantor', 'kode', 'nama', 'status',
    ];

    public function departemen(): BelongsTo
    {
        return $this->belongsTo(Departemen::class, 'id_departemen');
    }

    public function kantor(): BelongsTo
    {
        return $this->belongsTo(Kantor::class, 'id_kantor');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_unit');
    }
}
