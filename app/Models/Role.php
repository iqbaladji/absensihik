<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $table = 'm_role';

    protected $fillable = ['slug', 'nama', 'deskripsi', 'hak_akses', 'is_system'];

    protected $casts = [
        'hak_akses' => 'array',
        'is_system' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_role');
    }
}
