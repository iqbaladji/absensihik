<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jabatan extends Model
{
    protected $table = 'm_jabatan';

    protected $fillable = [
        'kode', 'nama', 'level', 'status',
    ];

    protected $casts = [
        'level' => 'integer',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_jabatan');
    }
}
